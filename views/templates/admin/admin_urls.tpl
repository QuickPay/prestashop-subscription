<div class="panel" id="admin_urls">
  <div class="panel-heading"><i class="icon-info"></i>Admin urls</div>
  {foreach $urls as $url}
      <a href="{$url['url']}" style="display: block; width: 100%; font-size: 20px; font-weight: bold; line-height: 26px; margin-bottom: 10px">{$url['name']}</a>
  {/foreach}
</div>
