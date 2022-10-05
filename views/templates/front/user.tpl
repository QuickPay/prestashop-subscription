{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='My subscriptions' mod='quickpaysubscription'}
{/block}

{block name='page_content'}
  <h6>{l s='Here are your current active subscriptions you\'ve created since your account was created.' mod='quickpaysubsciption'}</h6>
    <div class="messages" style="display: none"></div>
    {if isset($subscriptions) && $subscriptions}
      <table class="table table-striped table-bordered table-labeled hidden-sm-down">
        <thead class="thead-default">
        <tr>
          <th>{l s='Subscription ID' d='Shop.Theme.Checkout'}</th>
          <th>{l s='Products' d='Shop.Theme.Checkout'}</th>
          <th>{l s='Frequency' d='Shop.Theme.Checkout'}</th>
          <th>{l s='Last recurring time' d='Shop.Theme.Checkout'}</th>
          <th>{l s='Status' d='Shop.Theme.Checkout'}</th>
          {if $userCanCancel}
            <th></th>
          {/if}
        </tr>
        </thead>
        <tbody>
        {foreach from=$subscriptions item=subscription}
          <tr>
            <th scope="row">{$subscription.id}</th>
            <td>
                <table style="width: 100%;">
                    {foreach $subscription.products as $product}
                      {if isset($product['name']) && isset($product['link'])}
                        <tr>
                          <td style="width: 125px;"><img src="{$product['image']}" width=""/> </td>
                          <td><a href="{$product['link']}">{$product['name']}</a>{if isset($product['custom'])}<span style="display: block">{l s='Customized' mod='quickpaysubscription'}</span> {/if}</td>
                        </tr>
                      {/if}
                    {/foreach}
                </table>
            </td>
            <td class="hidden-md-down">
                {l s='Every' mod='quickpaysubscription'}
                {if $subscription.plan->frequency == 'daily'}
                    {if $subscription.frequency == 1}{l s='day' mod='quickpaysubscription'}{else}{$subscription.frequency} {l s='days' mod='quickpaysubscription'}{/if}
                {elseif $subscription.plan->frequency == 'weekly'}
                    {if $subscription.frequency == 1}{l s='week' mod='quickpaysubscription'}{else}{$subscription.frequency} {l s='weeks' mod='quickpaysubscription'}{/if}
                {elseif $subscription.plan->frequency == 'monthly'}
                    {if $subscription.frequency == 1}{l s='month' mod='quickpaysubscription'}{else}{$subscription.frequency} {l s='months' mod='quickpaysubscription'}{/if}
                {elseif $subscription.plan->frequency == 'yearly'}
                    {if $subscription.frequency == 1}{l s='year' mod='quickpaysubscription'}{else}{$subscription.frequency} {l s='years' mod='quickpaysubscription'}{/if}
                {/if}
            </td>
            <td>
                {$subscription.last_recurring}
            </td>
            <td class="text-sm-center">
              {if $subscription.status}
                <svg style="width:24px;height:24px" viewBox="0 0 24 24">
                  <path fill="#4cbb6c" d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" />
                </svg>
              {else}
                <svg style="width:24px;height:24px" viewBox="0 0 24 24">
                  <path fill="#ff4c4c" d="M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z" />
                </svg>
              {/if}
            </td>
              {if $userCanCancel}
                <td>
                    {if $subscription.status}<a href="#" class="cancelSubscription" data-id="{$subscription.id}">{l s='Cancel' mod='quickpaysubscription'}</a>{/if}
                </td>
              {/if}
          </>
        {/foreach}
        </tbody>
      </table>
    {/if}
{/block}
