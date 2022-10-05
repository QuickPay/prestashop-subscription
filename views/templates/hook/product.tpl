{if isset($plans) && $plans|count}
  <div class="clearfix"></div>
  <div class="row">
    <div class="col-md-12 d-flex flex-column py-2">
      <span class="text-primary bg-white font-weight-normal">{l s='This product has subscription options. If you would like to subscribe please select the Subscribe button and select the subscription plan' mod='quickpaysubscription'}</span>
      <div class="subscription_choice py-1">
        <div class="d-flex flex-row justify-content-start align-items-center">
          <label for="quickpaysubscription_cart_type_purchase" class="d-flex align-items-center">
            <input type="radio" name="quickpaysubscription_cart_type" id="quickpaysubscription_cart_type_purchase" value="1" {if !isset($subscribeProduct) || !$subscribeProduct}checked="checked"{/if}>
            <span class="pl-1" role="button">{l s='Purchase' mod='quickpaysubscription'}</span>
          </label>
        </div>
        <div class="d-flex flex-row justify-content-start align-items-center">
          <label for="quickpaysubscription_cart_type_subscribe" class="d-flex align-items-center">
            <input type="radio" name="quickpaysubscription_cart_type" id="quickpaysubscription_cart_type_subscribe" value="2" {if isset($subscribeProduct) && $subscribeProduct}checked="checked"{/if}>
            <span class="pl-1" role="button">{l s='Subscribe' mod='quickpaysubscription'}</span>
          </label>
        </div>
        <div class="subscription_plans {if isset($subscribeProduct) && $subscribeProduct}active{/if}">
          <ul>
          </ul>
          <div class="frequency_data">
            <span class="control-label">{l s='Select how frequently you would like to receive the product:' mod='quickpaysubscription'}</span>
            <select name="quickpaysubscription_selected_plan_frequency" class="form-control form-control-select">
                {foreach $plans as $plan}
                    {foreach $plan->cycle as $cycle}
                        {if $plan->plan->frequency == 'daily'}
                          <option {if isset($subscribeProduct) && $subscribeProduct->id_plan && $subscribeProduct->id_plan == $plan->id_plan && $subscribeProduct->frequency == $cycle}selected="selected"{/if} data-id_plan="{$plan->id_plan}" value="{$cycle}">{$cycle} {if $cycle == 1}{l s='day' mod='quickpaysubscription'}{else}{l s='days' mod='quickpaysubscription'}{/if}</option>
                        {elseif $plan->plan->frequency == 'weekly'}
                          <option {if isset($subscribeProduct) && $subscribeProduct->id_plan && $subscribeProduct->id_plan == $plan->id_plan && $subscribeProduct->frequency == $cycle}selected="selected"{/if} data-id_plan="{$plan->id_plan}" value="{$cycle}">{$cycle} {if $cycle == 1}{l s='week' mod='quickpaysubscription'}{else}{l s='weeks' mod='quickpaysubscription'}{/if}</option>
                        {elseif $plan->plan->frequency == 'monthly'}
                          <option {if isset($subscribeProduct) && $subscribeProduct->id_plan && $subscribeProduct->id_plan == $plan->id_plan && $subscribeProduct->frequency == $cycle}selected="selected"{/if} data-id_plan="{$plan->id_plan}" value="{$cycle}">{$cycle} {if $cycle == 1}{l s='month' mod='quickpaysubscription'}{else}{l s='months' mod='quickpaysubscription'}{/if}</option>
                        {else}
                          <option {if isset($subscribeProduct) && $subscribeProduct->id_plan && $subscribeProduct->id_plan == $plan->id_plan && $subscribeProduct->frequency == $cycle}selected="selected"{/if} data-id_plan="{$plan->id_plan}" value="{$cycle}">{$cycle} {if $cycle == 1}{l s='year' mod='quickpaysubscription'}{else}{l s='years' mod='quickpaysubscription'}{/if}</option>
                        {/if}
                    {/foreach}
                {/foreach}
            </select>
          </div>
        </div>
      </div>
    </div>
    <input type="hidden" name="quickpaysubscription_token" value="{$quickpaysubscription_token}" />
    <input type="hidden" name="quickpaysubscription_ajax_url" value="{$quickpaysubscription_ajax_url}" />
    {if isset($subscribeProduct) && $subscribeProduct}
      <input type="hidden" name="quickpaysubscription_subscription_cart_id" value="{$subscribeProduct->id}" />
      <input type="hidden" name="quickpaysubscription_subscription_product" value="{$subscribeProduct->id_subscription_product}" />
    {/if}
  </div>
  <div class="clearfix"></div>
{/if}
