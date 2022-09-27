import template from './order-subscription.html.twig';
import './order-subscription.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('landimit-subscription-detail', {
    template,

    inject: ['repositoryFactory', 'acl', 'customFieldDataProviderService'],

    mixins: [
        'placeholder',
        'notification',
        'salutation',
    ],
    data() {
        return {
            isLoading: null,
            isSaveSuccessful: false,
            subscriptionId: null,
            subscription: {},
            lineItems: [],
            customFieldSets: null,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier),
        };
    },

    computed: {
        identifier() {
            return this.subscription.id;
        },

        repository() {
            return this.repositoryFactory.create('landimit_subscription');
        },

        stars() {
            if (this.subscription.points >= 0) {
                return this.subscription.points;
            }

            return 0;
        },

        languageCriteria() {
            const criteria = new Criteria();

            criteria.addSorting(Criteria.sort('name', 'ASC', false));

            return criteria;
        },

        tooltipSave() {
            if (!this.acl.can('subscription.editor')) {
                return {
                    message: this.$tc('sw-privileges.tooltip.warning'),
                    disabled: true,
                    showOnDisabledElements: true,
                };
            }

            const systemKey = this.$device.getSystemKey();

            return {
                message: `${systemKey} + S`,
                appearance: 'light',
            };
        },

        tooltipCancel() {
            return {
                message: 'ESC',
                appearance: 'light',
            };
        },

        showCustomFields() {
            return this.subscription && this.customFieldSets && this.customFieldSets.length > 0;
        },

        getOrderColumns() {
            return [
            {
                property: 'name',
                label: 'landimit.subscription.columnProductName',
                allowResize: true,
            },
            {
                property: 'unitPrice',
                label: 'landimit.subscription.columnUnitPrice',
                routerLink: 'landimit.subscription.detail',
                allowResize: true,
                primary: true,
            },
            
            {
                property: 'totalPrice',
                label: 'landimit.subscription.columnTotalPrice',
                allowResize: true,
            }
            ];
        },

    },

    watch: {
        '$route.params.id'() {
            this.createdComponent();
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.$route.params.id) {
                this.subscriptionId = this.$route.params.id;

                this.loadEntityData();
            }
        },

        loadEntityData() {
            this.isLoading = true;
            const criteria = new Criteria();

            criteria.addAssociation('customer');
            criteria.addAssociation('currency');
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('lineItems');
            criteria.addAssociation('lineItems.product');
            const context = { ...Shopware.Context.api, inheritance: true };

            this.repository.get(this.subscriptionId, context, criteria).then((subscription) => {
                this.subscription = subscription;
                this.lineItems = subscription.lineItems;
                this.isLoading = false;
            });
        },

        
       

        onSave() {
            this.isSaveSuccessful = false;
            const messageSaveError = this.$tc(
                'global.notification.notificationSaveErrorMessageRequiredFieldsInvalid',
            );

            this.repository.save(this.subscription).then(() => {
                this.isSaveSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    message: messageSaveError,
                });
            });
        },

        onSaveFinish() {
            this.loadEntityData();
            this.isSaveSuccessful = false;
        },

        onCancel() {
            this.$router.push({ name: 'landimit.subscription.index' });
        },
    },
});
