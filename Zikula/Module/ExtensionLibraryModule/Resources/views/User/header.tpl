<div class="row">
    {if $displayFilter|default:false}
        <div class="pull-right">
            {elGetChosenExtensionType assign='extensionType'}
            {elGetAvailableExtensionTypes assign='extensionTypes'}
            <div class="btn-group">
                <button type="button" name="typeFilter" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-filter fa-lg"></i>&nbsp;{gt text="Type"}: {if $extensionType == 'all'}{gt text='All'}{else}{$extensionTypes.$extensionType}{/if}&nbsp;<span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='filter' filterType="extensionType" filter="all" returnUrl=$request->getRequestUri()|urlencode}">{gt text='All Types'}</a></li>
                    <li role="presentation"  class="divider"></li>
                    {foreach from=$extensionTypes key='id' item='text'}
                        <li><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='filter' filterType="extensionType" filter=$id returnUrl=$request->getRequestUri()|urlencode}">{$text}</a></li>
                    {/foreach}
                </ul>
            </div>
            {elGetChosenCore assign='coreVersion'}
            {elGetAvailableCoreVersions assign='coreVersions'}
            <div class="btn-group">
                <button type="button" name="coreFilter" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-filter fa-lg"></i>&nbsp;{gt text="Core"}: {if $coreVersion == 'all'}{gt text='All'}{else}{$coreVersion}{/if}&nbsp;<span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu" role="menu" id="el-core-version-filter">
                    <li><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='filter' filterType="coreVersion" filter="all" returnUrl=$request->getRequestUri()|urlencode}">{gt text='All versions'}</a></li>
                    <li role="presentation"  class="divider"></li>
                    <li role="presentation" class="dropdown-header">Current versions</li>
                    {foreach from=$coreVersions.supported key="version" item="foo"}
                        <li><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='filter' filterType="coreVersion" filter=$version returnUrl=$request->getRequestUri()|urlencode}">{$version}</a></li>
                    {/foreach}
                    <li role="presentation"  class="divider"></li>
                    <li role="presentation" class="dropdown-header">Outdated versions</li>
                    {foreach from=$coreVersions.outdated key="version" item="foo"}
                        <li><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='filter' filterType="coreVersion" filter=$version returnUrl=$request->getRequestUri()|urlencode}">{$version}</a></li>
                    {/foreach}
                    <li role="presentation"  class="divider"></li>
                    <li role="presentation" class="dropdown-header">Developmental versions</li>
                    {foreach from=$coreVersions.dev key="version" item="foo"}
                        <li><a href="{modurl modname='ZikulaExtensionLibraryModule' type='user' func='filter' filterType="coreVersion" filter=$version returnUrl=$request->getRequestUri()|urlencode}">{$version}</a></li>
                    {/foreach}
                </ul>
            </div>
        </div>
    {/if}
    <h1><img src="{modgetimage}" alt="" style="vertical-align: bottom; padding-right:.5em;" /><i>{modgetinfo info='displayname'}</i></h1>
    <hr />
    <ol class="breadcrumb">
        <li><a href="el/">{gt text='Library Home'}</a></li>
        {foreach from=$breadcrumbs item="breadcrumb" name="breadcrumbs"}
            {if $smarty.foreach.breadcrumbs.last}
                <li class="active">{$breadcrumb.title}</li>
            {else}
                <li><a href="{$breadcrumb.route}">{$breadcrumb.title}</a></li>
            {/if}
        {/foreach}
    </ol>
</div>