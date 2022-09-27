const { Application } = Shopware;
import '../core/component/sw-condition-subscription';


Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('subscription', {
        component: 'sw-condition-subscription',
        label: 'Subscription',
        scopes: ['global']
    });

    return ruleConditionService;
});