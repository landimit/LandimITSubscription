import './page/order-subscription-list';
import './page/order-subscription-detail';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('landimit-subscription', {
    type: 'plugin',
    name: 'landimit-order-subscription.csvExportNav',
    title: 'landimit-order-subscription.csvExportTop',
    description: 'landimit-order-subscription.pluginDescriptionText',
    color: '#62ff80',
    icon: 'default-object-lab-flask',
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
    routes: {
        index: {
            component: 'landimit-subscription-list',
            path: 'index',
        }, 
        detail: {
            component: 'landimit-subscription-detail',
            path: 'detail/:id',
            meta: {
                privilege: 'subscription.viewer',
                appSystem: {
                    view: 'detail',
                },
            },
            children: orderDetailChildren(),
            props: {
                default: ($route) => {
                    return { subscriptionId: $route.params.id };
                },
            },
        },
    },
    navigation: [
        {
            id: 'landimit-subscription-page-nav',
            color: '#FFD700',
            path: 'landimit.subscription.index',
            label: 'landimit-subscription.NavItem',
            parent: 'sw-order',
            position: 58,
            privilege: 'order.viewer'
        }
    ]
});


function orderDetailChildren() {
  
    return {
        base: {
            component: 'landimit-subscription-detail-base',
            path: 'base',
            meta: {
                parentPath: 'landimit.subscription.index',
                privilege: 'order.viewer',
            },
        },
    };
}
