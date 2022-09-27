import SubscriptionFormPlugin from './subscription-form/subscription-form.plugin';
const PluginManager = window.PluginManager;
PluginManager.register('SubscriptionFormPlugin', SubscriptionFormPlugin, '[data-subscription-form]');


// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}

