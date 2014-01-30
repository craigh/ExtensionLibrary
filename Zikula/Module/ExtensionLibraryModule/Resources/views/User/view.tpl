{include file='User/header.tpl'}
<div class="row">
    {foreach from=$extensions item="extension" name='loop'}
        {if ($smarty.foreach.loop.iteration - 1) % 3 == 0 && $smarty.foreach.loop.iteration != 1}
        </div>
        <hr />
        <div class="row">
        {/if}
        <div class="col-sm-4">
            <div class="media">
                <a class="pull-left" href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='display' id=$extension->getId()}">
                    <div class="iconStack">
                        <img class="media-object img-thumbnail" src="{$extension->getIcon()}" alt="" width="90" height="90" />
                        {if isset($extension.vendor.ownerEmail)}
                            <img class="img-thumbnail vendorIcon" src="http://www.gravatar.com/avatar/{$extension.vendor.ownerEmail|md5}?d=identicon" alt="">
                        {/if}
                    </div>
                </a>
                <div class="media-body">
                    <h4 class="media-heading"><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='display' id=$extension->getId()}">{$extension.title|safetext}&nbsp;&nbsp;<small>{$extension.type}</small></a></h4>
                    <ul class="list-unstyled">
                        <li>{$extension.vendor.ownerName|default:''}</li>
                        <li><i class="fa fa-external-link"></i> <a href="{$extension.vendor.ownerUrl|default:''}">{gt text="Vendor Website"}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    {/foreach}
</div>