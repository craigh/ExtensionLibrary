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

namespace Zikula\Module\ExtensionLibraryModule\Entity;

use Zikula\Core\Doctrine\EntityAccess;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Core version @ORM\Entity
 * @ORM\Table(name="el_core_releases")
 */
class CoreReleaseEntity extends EntityAccess
{
    const STATE_SUPPORTED = 1;

    const STATE_OUTDATED = 2;

    const STATE_PRERELEASE = 3;

    const STATE_DEVELOPMENT = 4;

    /**
     * Id field - NOT autogenerated. The GitHub release id is used.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="NONE")
     * @var int
     */
    private $id;

    /**
     * Core version (semver)
     *
     * @ORM\Column(type="string", length=10)
     * @var string
     */
    private $semver = '';

    /**
     * Release name.
     *
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $name = '';

    /**
     * Release description.
     *
     * @ORM\Column(type="text")
     * @var string
     */
    private $description = '';

    /**
     * Core status (supported, outdated, development)
     *
     * @ORM\Column(type="integer")
     * @var int
     */
    private $status;

    /**
     * Core assets.
     *
     * @ORM\Column(type="array")
     * @var array
     */
    private $assets;

    /**
     * Source code download urls.
     *
     * @ORM\Column(type="array")
     * @var array
     */
    private $sourceUrls;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public static function statusToText($status)
    {
        $dom = \ZLanguage::getModuleDomain('ZikulaExtensionLibraryModule');
        $translation = array (
            self::STATE_OUTDATED => __('Outdated version', $dom),
            self::STATE_DEVELOPMENT => __('Development version', $dom),
            self::STATE_PRERELEASE => __('Prerelease', $dom),
            self::STATE_SUPPORTED => __('Supported version', $dom)
        );

        return $translation[$status];
    }

    /**
     * @param array $assets
     */
    public function setAssets($assets)
    {
        $this->assets = $assets;
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $semver
     */
    public function setSemver($semver)
    {
        $this->semver = $semver;
    }

    /**
     * @return string
     */
    public function getSemver()
    {
        return $this->semver;
    }

    /**
     * @param array $sourceUrls
     */
    public function setSourceUrls($sourceUrls)
    {
        $this->sourceUrls = $sourceUrls;
    }

    /**
     * @return array
     */
    public function getSourceUrls()
    {
        return $this->sourceUrls;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
