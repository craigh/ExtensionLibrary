{include file='User/header.tpl' displayFilter=true}
<div class="row">
    {foreach from=$extensions item="extension" name='loop'}
        {if ($smarty.foreach.loop.iteration - 1) % 3 == 0 && $smarty.foreach.loop.iteration != 1}
        </div>
        <hr />
        <div class="row">
        {/if}
        <div class="col-sm-4">
            <div class="media extension-display" data-content="[version {$extension.newestVersion.semver}] {$extension.description|safehtml}">
                <a class="pull-left" href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='display' extension_slug=$extension.titleSlug}">
                    <div class="iconStack">
                        <img class="media-object img-thumbnail" src="{$extension.icon}" alt="" width="90" height="90" />
                        <img class="img-thumbnail vendorIcon" src="{$extension.vendor.logo}" alt="">
                    </div>
                </a>
                <div class="media-body">
                    <h4 class="media-heading"><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='display' extension_slug=$extension.titleSlug}">{$extension.title|safetext}</a></h4>
                    <em class="text-muted">{$extension.typeForDisplay}</em>
                    <ul class="list-unstyled">
                        {if !empty($extension.vendor.title)}<li>{$extension.vendor.title|safehtml}</li>{/if}
                        {if !empty($extension.vendor.url)}<li><i class="fa fa-external-link"></i> <a href="{$extension.vendor.url}">{gt text="Vendor Website"}</a></li>{/if}
                    </ul>
                </div>
            </div>
        </div>
    {/foreach}
</div>
{include file='User/footer.tpl'}
<script>
    jQuery(document).ready(function() {
        jQuery(".extension-display").popover({container: "body", trigger: "hover", placement: "top"});
    });
</script>