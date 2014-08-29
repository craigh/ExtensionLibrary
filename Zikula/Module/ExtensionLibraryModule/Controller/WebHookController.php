<?php
/**
 * Copyright Zikula Foundation 2014 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\ExtensionLibraryModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route; // used in annotations - do not remove
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Zikula\Core\ModUrl;
use Zikula\Core\Response\PlainResponse;
use Zikula\Module\ExtensionLibraryModule\Entity\ExtensionVersionEntity;
use Zikula\Module\ExtensionLibraryModule\Entity\VendorEntity;
use Zikula\Module\ExtensionLibraryModule\Entity\ExtensionEntity;
use Zikula\Module\ExtensionLibraryModule\Exception\ClientException;
use Zikula\Module\ExtensionLibraryModule\Exception\ServerException;
use Zikula\Module\ExtensionLibraryModule\Manager\ReleaseManager;
use Zikula\Module\ExtensionLibraryModule\Manager\ManifestManager;
use Zikula\Module\ExtensionLibraryModule\Manager\ComposerManager;
use Zikula\Module\ExtensionLibraryModule\Manager\PayloadManager;
use Zikula\Module\ExtensionLibraryModule\Manager\ImageManager;
use ModUtil;
use Zikula\Core\Hook\ProcessHook;
use ZLanguage;
use Zikula\Module\ExtensionLibraryModule\Util;

/**
 * Jenkins and GitHub Webhook access points.
 */
class WebHookController extends \Zikula_AbstractController
{
    /**
     * @Route("/webhook", options={"i18n"=false})
     * @Method("POST")
     */
    public function extensionAction()
    {
        $responseText = "";
        try {
            $payloadManager = new PayloadManager($this->request);
            $jsonPayload = $payloadManager->getJsonPayload();

            ///// (1) Do some general validation of the tag, the json files.
            // check 'refs' for tags, if none, then return
            if (!strpos($jsonPayload->ref, 'tags')) {
                $responseText .= "Event ignored - no tags found.";
                return new PlainResponse($responseText, Response::HTTP_OK);
            }

            // fetch the manifest, validate and get contents
            $manifestManager = new ManifestManager(
                $jsonPayload->repository->owner->name,
                $jsonPayload->repository->name,
                $jsonPayload->ref
            );
            if ($manifestManager->hasDecodingErrors()) {
                $responseText .= "Cannot decode manifest. Violations:\n";
                foreach ($manifestManager->getDecodingErrors() as $error) {
                    $responseText .= "- $error\n";
                }
                Util::log("{$jsonPayload->repository->name}: " . implode("\n", $manifestManager->getValidationErrors()), Util::LOG_PROD);
                return new PlainResponse($responseText, Response::HTTP_BAD_REQUEST);
            }

            $manifestContent = $manifestManager->getContent();
            if (empty($manifestContent)) {
                $responseText .= "Manifest file does not validate. Violations:\n";
                foreach ($manifestManager->getValidationErrors() as $error) {
                    $responseText .= "- " . sprintf("[%s] %s", $error['property'], $error['message']) . "\n";
                }
                Util::log("{$jsonPayload->repository->name}: " . implode("\n", $manifestManager->getValidationErrors()), Util::LOG_PROD);
                return new PlainResponse($responseText, Response::HTTP_BAD_REQUEST);
            }

            // fetch the composer.json file, validate and get contents
            $composerManager = new ComposerManager(
                $jsonPayload->repository->owner->name,
                $jsonPayload->repository->name,
                $jsonPayload->ref,
                $manifestContent->version->composerpath
            );
            $composerContent = $composerManager->getContent();
            if (empty($composerContent)) {
                $responseText .= "Composer file does not validate. Violations:\n";
                foreach ($composerManager->getValidationErrors() as $error) {
                    $responseText .= "- " . (sprintf("[%s] %s", $error['property'], $error['message'])) . "\n";
                }
                Util::log("{$jsonPayload->repository->name}: " . implode("\n", $composerManager->getValidationErrors()), Util::LOG_PROD);
                return new PlainResponse($responseText, Response::HTTP_BAD_REQUEST);
            }

            ///// (2) Get or create vendor.
            // Get the vendor id from the repository's ownername.
            $gitHubClient = Util::getGitHubClient();
            $user = $gitHubClient->api('user')->show($jsonPayload->repository->owner->name);
            $userId = $user['id'];

            // Check vendor exists, if not create new vendor
            $vendor = $this->entityManager->find('ZikulaExtensionLibraryModule:VendorEntity', $userId);
            if (!$vendor) {
                // not found, create
                $vendor = new VendorEntity($userId, $jsonPayload->repository->owner->name);
                $this->entityManager->persist($vendor);
                $responseText .= sprintf('Vendor (%s) created', "$userId/{$jsonPayload->repository->owner->name}") . "\n";
            } else {
                // found
                $responseText .= sprintf('Vendor (%s) found', "$userId/{$jsonPayload->repository->owner->name}") . "\n";
            }

            ///// (3) Handle vendor logo.
            if (!empty($manifestContent->vendor) && !empty($manifestContent->vendor->logo)) {
                $imageManager = new ImageManager($manifestContent->vendor->logo);
                if ($imageManager->import()) {
                    $manifestContent->vendor->logo = $imageManager->getName();
                } else {
                    $responseText .= "Invalid vendor logo. Violations:\n";
                    foreach ($imageManager->getValidationErrors() as $error) {
                        $responseText .= "- $error\n";
                    }
                    Util::log("{$jsonPayload->repository->name}: " . implode("\n", $imageManager->getValidationErrors()), Util::LOG_PROD);
                }
            }

            ///// (4) Set vendor data.
            // @todo Remove this and exclusively use the Webinterface to set vendor information.
            $vendor->mergeManifest($manifestContent);
            $vendor->mergeComposer($composerContent);

            ///// (5) Get or create extension.
            $repositoryId = (int)$jsonPayload->repository->id;
            // check extension exists, if not create new extension
            if ($vendor->hasExtensionById($repositoryId)) {
                $responseText .= sprintf('Extension (%s) found', "$repositoryId/{$jsonPayload->repository->name}") . "\n";
                $extension = $vendor->getExtensionById($repositoryId);
            } else {
                // not found, create new extension and assign to vendor
                $extension = new ExtensionEntity(
                    $vendor,
                    $repositoryId,
                    $jsonPayload->repository->name,
                    $manifestContent->extension->title,
                    $composerContent->description,
                    $composerContent->type
                );
                $vendor->addExtension($extension);
                $this->entityManager->persist($extension);
                $responseText .= sprintf('Extension (%s) created', "$repositoryId/{$jsonPayload->repository->name}") . "\n";
            }

            ///// (6) Handle extension icon.
            if (!empty($manifestContent->extension->icon)) {
                $imageManager = new ImageManager($manifestContent->extension->icon);
                if ($imageManager->import()) {
                    $manifestContent->extension->icon = $imageManager->getName();
                } else {
                    $manifestContent->extension->icon = '';
                    $responseText .= "Invalid extension icon. Violations:\n";
                    foreach ($imageManager->getValidationErrors() as $error) {
                        $responseText .= "- $error\n";
                    }
                    Util::log("{$jsonPayload->repository->name}: " . implode("\n", $imageManager->getValidationErrors()), Util::LOG_PROD);
                }
            }

            ///// (7) Set extension data.
            $extension->mergeManifest($manifestContent);
            $extension->mergeComposer($composerContent);

            ///// (8) Create or check extension version.
            // compare version to newest available. If newer, add new version
            list(, , $semver) = explode('/', $jsonPayload->ref);
            $newestVersion = $extension->getNewestVersion();
            if (empty($newestVersion) || (version_compare($semver, $newestVersion->getSemver(), '>'))) {
                // add new version of extension
                $version = new ExtensionVersionEntity(
                    $extension,
                    $semver,
                    $manifestContent->version->dependencies,
                    $composerContent->license
                );
                $extension->addVersion($version);
                $this->entityManager->persist($version);

                ///// (9) Set extension version data.
                $version->mergeManifest($manifestContent);
                $version->mergeComposer($composerContent);
                $responseText .= sprintf("Version %s added to extension %s\n", $semver, $extension->getTitle());
            } else {
                $responseText .= sprintf("The version %s was not added because it was the same or older than the current version (%s).\n",
                    $semver,
                    $newestVersion->getSemver());
                // return without flushing since there should be no changes if version isn't new
                Util::log("{$jsonPayload->repository->name}: $responseText", Util::LOG_PROD);
                return new PlainResponse($responseText, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            ///// (10) Add keywords via the Tag module when hooked.
            /** @var $hookDispatcher \Zikula\Component\HookDispatcher\StorageInterface */
            $hookDispatcher = \ServiceUtil::get('hook_dispatcher');
            $url = new ModUrl($this->name, 'user', 'display', ZLanguage::getLanguageCode(), array('extension_slug' => $extension->getTitleSlug()));
            if (ModUtil::available('Tag')) {
                $bindings = $hookDispatcher->getBindingsBetweenOwners($this->name, 'Tag');
                if (count($bindings) > 0) {
                    $areaId = $hookDispatcher->getAreaId('subscriber.el.ui_hooks.extension');
                    $args = array(
                        'module' => $this->name,
                        'objectId' => $extension->getId(),
                        'areaId' => $areaId,
                        'objUrl' => $url,
                        'hookdata' => array('tags' => $manifestContent->version->keywords),
                    );
                    ModUtil::apiFunc('Tag', 'user', 'tagObject', $args);
                }
            }

            ///// (11) Handle Dizkus Hook.
            if (ModUtil::available('ZikulaDizkusModule')) {
                $bindings = $hookDispatcher->getBindingsBetweenOwners($this->name, 'ZikulaDizkusModule');
                if (count($bindings) > 0) {
                    $this->request->request->set('dizkus', array('createTopic' => 1));
                }
            }
            $this->dispatchHooks('el.ui_hooks.community.process_edit', new ProcessHook($extension->getId(), $url));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }

        Util::log("{$jsonPayload->repository->name}: $responseText", Util::LOG_PROD);
        return new PlainResponse($responseText, Response::HTTP_OK);
    }

    /**
     * @Route("/webhook-core", options={"i18n"=false})
     * @Method("POST")
     */
    public function coreAction(Request $request)
    {
        try {
            $payloadManager = new PayloadManager($request, true);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }

        $securityToken = $this->getVar('github_webhook_token');
        if (!empty($securityToken)) {
            $signature = $request->headers->get('X-Hub-Signature');
            if (empty($signature)) {
                return new PlainResponse('Missing security token!', Response::HTTP_BAD_REQUEST);
            }
            $computedSignature = $this->computeSignature($payloadManager->getRawPayload(), $securityToken);

            if (!$this->secure_equals($computedSignature, $signature)) {
                return new PlainResponse('Signature did not match!', Response::HTTP_BAD_REQUEST);
            }
        }

        $event = $this->request->headers->get('X-Github-Event');
        if (empty($event)) {
            return new PlainResponse('"X-Github-Event" header is missing!', Response::HTTP_BAD_REQUEST);
        }
        $useragent = $request->headers->get('User-Agent');
        if (strpos($useragent, 'GitHub-Hookshot/') !== 0) {
            // User agent does not match "GitHub-Hookshot/*"
            return new PlainResponse('User-Agent not allowed!', Response::HTTP_BAD_REQUEST);
        }

        if ($event != 'release') {
            // We do not listen to that event.
            return new PlainResponse('Event ignored!', Response::HTTP_OK);
        }

        $jsonPayload = $payloadManager->getJsonPayload();
        // See https://developer.github.com/v3/activity/events/types/#releaseevent
        if ($jsonPayload['action'] != 'published') {
            return new PlainResponse('Release event ignored (action != "published")!', Response::HTTP_OK);
        }

        $repo = $this->getVar('github_core_repo', 'zikula/core');
        if ($jsonPayload['repository']['full_name'] != $repo) {
            return new PlainResponse('Release event ignored (repository != "' . $repo . '")!', Response::HTTP_BAD_REQUEST);
        }

        /** @var ReleaseManager $releaseManager */
        $releaseManager = $this->get('zikulaextensionlibrarymodule.releasemanager');
        $releaseManager->updateGitHubRelease($jsonPayload['release']);

        return new PlainResponse('Release list reloaded!', Response::HTTP_OK);
    }

    /**
     * @Route("/webhook-jenkins/{code}", options={"i18n"=false})
     * @Method("POST")
     */
    public function jenkinsAction($code)
    {
        if (!$this->secure_equals($code, $this->getVar('jenkins_token', ''))) {
            throw new AccessDeniedHttpException();
        }

        $releaseManager = $this->get('zikulaextensionlibrarymodule.releasemanager');
        $releaseManager->reloadReleases('jenkins');

        return new PlainResponse('Jenkins builds reloaded.', Response::HTTP_OK);
    }

    /**
     * Compute signature from payload using the security token.
     *
     * @param $payload
     * @param $securityToken
     *
     * @return string
     */
    private function computeSignature($payload, $securityToken)
    {
        return 'sha1=' . hash_hmac('sha1', $payload, $securityToken);
    }

    /**
     * Compares two strings $a and $b in length-constant time.
     *
     * @param $a
     * @param $b
     *
     * @return bool
     *
     * https://crackstation.net/hashing-security.htm#slowequals
     */
    private function secure_equals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $diff === 0;
    }

    private function handleException(\Exception $e)
    {
        switch (true) {
            case $e instanceof ClientException:
                $text = $e->getMessage();
                $code = $e->getCode();
                break;
            case $e instanceof ServerException:
                $text = "Something went wrong at our server. Please report this issue.\n\n{$e->getMessage()}\n{$e->getTraceAsString()}";
                $code = $e->getCode();
                break;
            default:
                $text = "Something unexpected happend. Please report this issue.\n\n{$e->getMessage()}\n{$e->getTraceAsString()}";
                $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new PlainResponse($text, $code);
    }
}
