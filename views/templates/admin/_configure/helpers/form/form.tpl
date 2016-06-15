{*
* 2016 Michael Dekker
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@michaeldekker.com so we can send you a copy immediately.
*
*  @author    Michael Dekker <prestashop@michaeldekker.com>
*  @copyright 2016 Michael Dekker
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{extends file="helpers/form/form.tpl"}

{block name="input"}
	{if $input.type == 'switch' && $smarty.const._PS_VERSION_|@addcslashes:'\'' < '1.6'}
		{foreach $input.values as $value}
			<input type="radio" name="{$input.name|escape:'htmlall':'UTF-8'}"
				   id="{$input.name|escape:'htmlall':'UTF-8'}_{$value.id|escape:'htmlall':'UTF-8'}"
				   value="{$value.value|escape:'htmlall':'UTF-8'}"
				   {if $fields_value[$input.name] == $value.value}checked="checked"{/if}
					{if isset($input.disabled) && $input.disabled}disabled="disabled"{/if} />
			<label class="t" for="{$input.name|escape:'htmlall':'UTF-8'}_{$value.id|escape:'htmlall':'UTF-8'}">
				{if isset($input.is_bool) && $input.is_bool == true}
					{if $value.value == 1}
						<img src="../img/admin/enabled.gif" alt="{$value.label|escape:'htmlall':'UTF-8'}"
							 title="{$value.label|escape:'htmlall':'UTF-8'}"/>
					{else}
						<img src="../img/admin/disabled.gif" alt="{$value.label|escape:'htmlall':'UTF-8'}"
							 title="{$value.label|escape:'htmlall':'UTF-8'}"/>
					{/if}
				{else}
					{$value.label|escape:'htmlall':'UTF-8'}
				{/if}
			</label>
			{if isset($input.br) && $input.br}<br/>{/if}
			{if isset($value.p) && $value.p}<p>{$value.p|escape:'htmlall':'UTF-8'}</p>{/if}
		{/foreach}
	{elseif $input.type == 'maintenance_ip' && $smarty.const._PS_VERSION_|@addcslashes:'\'' >= '1.6'}
		<script type="text/javascript">
			function addRemoteAddr() {ldelim}
				var length = $('input[name={$input.name|escape:'htmlall':'UTF-8'}]').attr('value').length;
				if (length > 0)
					$('input[name={$input.name|escape:'htmlall':'UTF-8'}]').attr('value', $('input[name={$input.name|escape:'htmlall':'UTF-8'}]').attr('value') + ',{Tools::getRemoteAddr()|escape:'javascript':'UTF-8'}');
				else
					$('input[name={$input.name|escape:'htmlall':'UTF-8'}]').attr('value', '{Tools::getRemoteAddr()|escape:'javascript':'UTF-8'}');
				{rdelim}
		</script>
		<div class="col-lg-9">
			<div class="row">
				<div class="col-lg-8">
					<input type="text" id="{$input.name|escape:'htmlall':'UTF-8'}"
						   name="{$input.name|escape:'htmlall':'UTF-8'}" value="{$fields_value[$input.name]|escape:'htmlall':'UTF-8'}"/>
				</div>
				<div class="col-lg-1">
					<button type="button" class="btn btn-default" onclick="addRemoteAddr();"><i
								class="icon-plus"></i> {l s='Add my IP' mod='jssupercache'}</button>
				</div>
			</div>
		</div>
	{elseif $input.type == 'javascript_table' && $smarty.const._PS_VERSION_|@addcslashes:'\'' >= '1.6'}
		<section class="filter_panel">
			<header class="clearfix">
				<div class="pull-right">
					<a class="btn btn-default" href="{$module_page}&empty_js_cache=1"><i class="icon-trash"></i> {l s='Empty cache' mod='jssupercache'}</a>
				</div>
			</header>
			<section class="filter_list">
				<ul class="list-unstyled sortable {$input.name|escape:'htmlall':'UTF-8'}_SORTABLE">
					{if $input.values|count > 0}
						{for $index=0 to count($input.values) - 1}
							<li class="filter_list_item" draggable="true" id="jsfile_{$index|escape:'htmlall':'UTF-8'}">
								<div class="col-lg-2">
									<label class="switch prestashop-switch fixed-width-lg">
										<span class="switch prestashop-switch fixed-width-lg">
										<input type="radio"
											   name="{$input.name|escape:'htmlall':'UTF-8'}_ENABLED_{$index|escape:'htmlall':'UTF-8'}"
											   id="{$input.name|escape:'htmlall':'UTF-8'}_ENABLED_{$index|escape:'htmlall':'UTF-8'}_on"
											   value="1" {if $input.values[$index]['enabled']}checked="checked"{/if}>
										<label for="{$input.name|escape:'htmlall':'UTF-8'}_ENABLED_{$index|escape:'htmlall':'UTF-8'}_on">{l s='Yes'}</label>
										<input type="radio"
											   name="{$input.name|escape:'htmlall':'UTF-8'}_ENABLED_{$index|escape:'htmlall':'UTF-8'}"
											   id="{$input.name|escape:'htmlall':'UTF-8'}_ENABLED_{$index|escape:'htmlall':'UTF-8'}_off"
											   value="" {if !$input.values[$index]['enabled']}checked="checked"{/if}>
										<label for="{$input.name|escape:'htmlall':'UTF-8'}_ENABLED_{$index|escape:'htmlall':'UTF-8'}_off">{l s='No'}</label>
										<a class="slide-button btn"></a>
										</span>
									</label>
								</div>
								<div class="col-lg-9">
										<h4>{l s='File: %s'|sprintf:$input.values[$index]['file'] mod='jssupercache'}</h4>
								</div>
								<input type="hidden" id="{$input.name|escape:'htmlall':'UTF-8'}_ORDER">
								<script type="text/javascript">
									{literal}
									function {/literal}{$input.name|escape:'htmlall':'UTF-8'}{literal}sortableJS() {
										var files = '';
										$('.{/literal}{$input.name|escape:'javascript':'UTF-8'}{literal}_SORTABLE').children().each(function () {
											files += $(this).attr('id').replace('jsfile_', '');
											files += ',';
										});
										$('input#{/literal}{$input.name|escape:'javascript':'UTF-8'}{literal}_ORDER').val(files.replace(/,$/, ''));
									}
									$(document).ready({/literal}{$input.name|escape:'htmlall':'UTF-8'}{literal}sortableJS);
									$('.{/literal}{$input.name|escape:'javascript':'UTF-8'}{literal}_SORTABLE').on('sortupdate', function(event, ui) {
										{/literal}{$input.name|escape:'htmlall':'UTF-8'}{literal}sortableJS();
									});
									{/literal}
								</script>
							</li>
						{/for}
					{/if}
				</ul>
			</section>
		</section>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name="label"}
	{if isset($input.label)}
		<label class="control-label col-lg-3{if isset($input.required) && $input.required && $input.type != 'radio'} required{/if}">
			{if isset($input.badge)}
				<span class="badge" id="selected_filters">{$input.badge|escape:'htmlall':'UTF-8'}</span>
			{/if}
			{if isset($input.hint)}
			<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="{if is_array($input.hint)}
													{foreach $input.hint as $hint}
														{if is_array($hint)}
															{$hint.text|escape:'quotes'}
														{else}
															{$hint|escape:'quotes'}
														{/if}
													{/foreach}
												{else}
													{$input.hint|escape:'quotes'}
												{/if}">
										{/if}
				{$input.label}
				{if isset($input.hint)}
			</span>
			{/if}
		</label>
	{/if}
{/block}