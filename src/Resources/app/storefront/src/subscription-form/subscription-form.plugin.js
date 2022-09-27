import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import Storage from 'src/helper/storage/storage.helper';
import DomAccess from 'src/helper/dom-access.helper';

export default class SubscriptionFormPlugin extends Plugin {

    static options = {
        subscriptionFormOptionNo: '.subscription-form-option-no',
        subscriptionFormOptionYes: '.subscription-form-option-yes',
        subscriptionOptions: '.subscription-options',
        productDetailPriceClass: '.product-detail-price',
        productDetailPriceSubscriptionClass: '.product-detail-price-subscription',
        productDetailPriceBadgeClass: '.list-price-badge',
        productDetailPriceWrapperClass: '.product-detail-list-price-wrapper'
    };

    init() {

        this._registerEvents();

    }

    /**
     * register all needed events
     *
     * @private
     */
    _registerEvents() {

        this.radioNo = DomAccess.querySelector(this.el, this.options.subscriptionFormOptionNo);
        this.radioYes = DomAccess.querySelector(this.el, this.options.subscriptionFormOptionYes);


        this.radioNo.addEventListener('click', event => this._onRadioNo(event), false);
        this.radioYes.addEventListener('click', event => this._onRadioYes(event), false);

        this.productDetailPriceClass = document.querySelector(this.options.productDetailPriceClass);
        this.productDetailPriceSubscriptionClass = document.querySelector(this.options.productDetailPriceSubscriptionClass);

        if(this.productDetailPriceClass)
            this.productDetailPriceClass.classList.add('d-none');

        if(this.productDetailPriceSubscriptionClass)
            this.productDetailPriceSubscriptionClass.classList.remove('d-none');



        this.productDetailPriceBadgeClass = document.querySelector(this.options.productDetailPriceBadgeClass);
        this.productDetailPriceWrapperClass = document.querySelector(this.options.productDetailPriceWrapperClass);

        if(this.productDetailPriceBadgeClass)
            this.productDetailPriceBadgeClass.classList.add('d-none');

        if(this.productDetailPriceWrapperClass)
            this.productDetailPriceWrapperClass.classList.add('d-none');

    }

    /**
     * returns if the current element is active
     *
     * @return {boolean}
     *
     * @private
     */
    _onRadioNo(event) {

        this.subscriptionOptions = DomAccess.querySelector(this.el, this.options.subscriptionOptions);

        this.subscriptionOptions.classList.add('d-none');

        if(this.productDetailPriceClass)
            this.productDetailPriceClass.classList.remove('d-none');

        if(this.productDetailPriceSubscriptionClass)
            this.productDetailPriceSubscriptionClass.classList.add('d-none');

        if(this.productDetailPriceBadgeClass)
            this.productDetailPriceBadgeClass.classList.remove('d-none');

        if(this.productDetailPriceWrapperClass)
            this.productDetailPriceWrapperClass.classList.remove('d-none');


        
    }
    _onRadioYes(event) {

        this.subscriptionOptions = DomAccess.querySelector(this.el, this.options.subscriptionOptions);

        this.subscriptionOptions.classList.remove('d-none');
        
        if(this.productDetailPriceClass)
            this.productDetailPriceClass.classList.add('d-none');

        if(this.productDetailPriceSubscriptionClass)
            this.productDetailPriceSubscriptionClass.classList.remove('d-none');

        if(this.productDetailPriceBadgeClass)
            this.productDetailPriceBadgeClass.classList.add('d-none');

        if(this.productDetailPriceWrapperClass)
            this.productDetailPriceWrapperClass.classList.add('d-none');

    }


}

