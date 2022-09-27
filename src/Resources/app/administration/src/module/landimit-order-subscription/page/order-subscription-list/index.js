import template from './order-subscription.html.twig';
import './order-subscription.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;


//LandimITCsvExporterApiService

Component.register('landimit-subscription-list', {
    template,

    inject: [
        'repositoryFactory',
        'stateStyleDataProviderService',
        'acl',
        'filterFactory',
        'feature',
    ],

    mixins: [
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            orders: [],
            sortBy: 'nextRenew',
            sortDirection: 'ASC',
            isLoading: false,
            filterLoading: false,
            showDeleteModal: false,
            availableAffiliateCodes: [true, false],
            availableCampaignCodes: [],

            /** @deprecated tag:v6.5.0 - values will be handled by filterFactory */
            affiliateCodeFilter: [],

            /** @deprecated tag:v6.5.0 - values will be handled by filterFactory */
            campaignCodeFilter: [],

            filterCriteria: [
                new Criteria.equals('archived', false)
            ],
            defaultFilters: [
                'archived'
            ],
            storeKey: 'grid.filter.order',
            activeFilterNumber: 0,
            showBulkEditModal: false,
            searchConfigEntity: 'landimit_subscription',
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        orderRepository() {
            return this.repositoryFactory.create('landimit_subscription');
        },

        orderColumns() {
            return this.getOrderColumns();
        },

        orderCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria.setTerm(this.term);

            
            this.sortBy.split(',').forEach(sortBy => {
                criteria.addSorting(Criteria.sort(sortBy, this.sortDirection));
            });
            

            this.filterCriteria.forEach(filter => {
                criteria.addFilter(filter);
            });

            criteria.addAssociation('customer');
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('lineItems');
            criteria.addAssociation('lineItems.product');
            //criteria.getAssociation('transactions').addSorting(Criteria.sort('createdAt'));

            return criteria;
        },

        filterSelectCriteria() {
            const criteria = new Criteria(1, 1);

            return criteria;
        },

        listFilters() {
        
            return this.filterFactory.create('landimit_subscription', {
                'archived': {
                    property: 'archived',
                    label: this.$tc('landimit-subscription.filters.archived.label'),
                    placeholder: this.$tc('landimit-subscription.filters.archived.placeholder'),
                    options: this.availableAffiliateCodes,
                }
            });

        },

        productCriteria() {
            const productCriteria = new Criteria();
            productCriteria.addAssociation('options.group');

            return productCriteria;
        },
    },

    watch: {
        orderCriteria: {
            handler() {
                this.getList();
            },
            deep: true,
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadFilterValues();
        },

        onEdit(order) {
            if (order?.id) {
                this.$router.push({
                    name: 'sw.order.detail',
                    params: {
                        id: order.id,
                    },
                });
            }
        },

        onInlineEditSave(order) {
            order.save();
        },

        onChangeLanguage() {
            this.getList();
        },

        async getList() {
            this.isLoading = true;

            let criteria = await Shopware.Service('filterService')
                .mergeWithStoredFilters(this.storeKey, this.orderCriteria);

            criteria = await this.addQueryScores(this.term, criteria);

            this.activeFilterNumber = criteria.filters.length;

            if (!this.entitySearchable) {
                this.isLoading = false;
                this.total = 0;

                return;
            }

            try {
                const response = await this.orderRepository.search(criteria);

                this.total = response.total;
                this.orders = response;
                this.isLoading = false;
            } catch {
                this.isLoading = false;
            }
        },

        getBillingAddress(order) {
            return order.addresses.find((address) => {
                return address.id === order.billingAddressId;
            });
        },

        disableDeletion(order) {
            if (!this.acl.can('order.deleter')) {
                return true;
            }

            return false;
        },

        getOrderColumns() {
            return [{
                property: 'autoIncrement',
                label: 'landimit.subscription.columnId',
                routerLink: 'landimit.subscription.detail',
                allowResize: true,
                primary: true,
            },
            {
                property: 'active',
                label: 'landimit.subscription.ColumnActive',
                allowResize: true,
            },
            {
                property: 'lineItems',
                label: 'landimit.subscription.columnProducts',
                allowResize: true,
            },
            {
                property: 'customer.firstName',
                dataIndex: 'customer.lastName,customer.firstName',
                label: 'landimit.subscription.columnCustomerName',
                allowResize: true,
            }, 
            {
                property: 'nextRenew',
                label: 'landimit.subscription.columnNextRenew',
                allowResize: true,
            },
            {
                property: 'createdAt',
                label: 'landimit.subscription.columnCreatedAt',
                allowResize: true,
            }
            ];
        },

        getVariantFromOrderState(order) {
            const style = this.stateStyleDataProviderService.getStyle(
                'order.state', order.stateMachineState.technicalName,
            );

            if (this.feature.isActive('FEATURE_NEXT_7530')) {
                return style.colorCode;
            }

            return style.variant;
        },

        getVariantFromPaymentState(order) {
            let technicalName = order.transactions.last().stateMachineState.technicalName;
            // set the payment status to the first transaction that is not cancelled
            for (let i = 0; i < order.transactions.length; i += 1) {
                if (!['cancelled', 'failed'].includes(order.transactions[i].stateMachineState.technicalName)) {
                    technicalName = order.transactions[i].stateMachineState.technicalName;
                }
            }

            const style = this.stateStyleDataProviderService.getStyle(
                'order_transaction.state', technicalName,
            );

            if (this.feature.isActive('FEATURE_NEXT_7530')) {
                return style.colorCode;
            }

            return style.variant;
        },

        getVariantFromDeliveryState(order) {
            const style = this.stateStyleDataProviderService.getStyle(
                'order_delivery.state', order.deliveries[0].stateMachineState.technicalName,
            );

            if (this.feature.isActive('FEATURE_NEXT_7530')) {
                return style.colorCode;
            }

            return style.variant;
        },

        loadFilterValues() {
            this.filterLoading = true;

            return this.orderRepository.search(this.filterSelectCriteria).then(({ aggregations }) => {
                //  this.availableAffiliateCodes = aggregations.affiliateCodes.buckets;
                this.availableCampaignCodes = aggregations.campaignCodes.buckets;
                this.filterLoading = false;

                return aggregations;
            }).catch(() => {
                this.filterLoading = false;
            });
        },

        /** @deprecated tag:v6.5.0 - will be handled by filterFactory */
        onChangeAffiliateCodeFilter(value) {
            this.affiliateCodeFilter = value;
            this.getList();
        },

        /** @deprecated tag:v6.5.0 - will be handled by filterFactory */
        onChangeCampaignCodeFilter(value) {
            this.campaignCodeFilter = value;
            this.getList();
        },

        onActivate(id) {

            const criteria = new Criteria();

            const context = { ...Shopware.Context.api, inheritance: true };

            this.orderRepository.get(id, context, criteria).then((subscription) => {
                subscription.active = true;
                this.orderRepository.save(subscription).then(() => {
                    this.$refs.orderGrid.resetSelection();
                    this.getList();
                });
            });
        },
        onDeactivate(id) {
            const criteria = new Criteria();

            const context = { ...Shopware.Context.api, inheritance: true };

            this.orderRepository.get(id, context, criteria).then((subscription) => {
                subscription.active = false;
                this.orderRepository.save(subscription).then(() => {
                    this.$refs.orderGrid.resetSelection();
                    this.getList();
                });
            });
        },
        onArchive(id) {
            const criteria = new Criteria();

            const context = { ...Shopware.Context.api, inheritance: true };

            this.orderRepository.get(id, context, criteria).then((subscription) => {
                subscription.archived = true;
                this.orderRepository.save(subscription).then(() => {
                    this.$refs.orderGrid.resetSelection();
                    this.getList();
                });
            });
        },
        onUnarchive(id) {
            const criteria = new Criteria();

            const context = { ...Shopware.Context.api, inheritance: true };

            this.orderRepository.get(id, context, criteria).then((subscription) => {
                subscription.archived = false;
                this.orderRepository.save(subscription).then(() => {
                    this.$refs.orderGrid.resetSelection();
                    this.getList();
                });
            });
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false;

            return this.orderRepository.delete(id).then(() => {
                this.$refs.orderGrid.resetSelection();
                this.getList();
            });
        },

        updateCriteria(criteria) {
            this.page = 1;

            this.filterCriteria = criteria;
        },

        getStatusCriteria(value) {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equals('stateMachine.technicalName', value));

            return criteria;
        },

        async onBulkEditItems() {
            await this.$nextTick();
            this.$router.push({ name: 'sw.bulk.edit.order' });
        },
    },
});