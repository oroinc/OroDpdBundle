import $ from 'jquery';
import _ from 'underscore';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import BaseComponent from 'oroui/js/app/components/base/component';

const DPDTransportSettingsComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        ratePolicySelector: 'select[name$="[transport][ratePolicy]"]',
        flatRatePriceValueSelector: 'input[name$="[transport][flatRatePriceValue]"]',
        ratesCsvSelector: 'input[name$="[transport][ratesCsv]"]',
        container: '.control-group'
    },

    /**
     * @inheritdoc
     */
    constructor: function DPDTransportSettingsComponent(options) {
        DPDTransportSettingsComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this.$elem = options._sourceElement;

        this.loadingMaskView = new LoadingMaskView({container: this.$elem});
        this.ratePolicyElem = $(this.$elem).find(this.options.ratePolicySelector);
        this.flatRatePriceValueElem = $(this.$elem).find(this.options.flatRatePriceValueSelector);
        this.ratesCsvElem = $(this.$elem).find(this.options.ratesCsvSelector);

        $(this.ratePolicyElem).on('change', this.onRatePolicyChange.bind(this));
        $(this.ratePolicyElem).trigger('change');
    },

    onRatePolicyChange: function() {
        const ratePolicyValue = $(this.ratePolicyElem).val();
        const self = this;

        if (ratePolicyValue === '0') { // DPDTransport::FLAT_RATE_POLICY
            $(this.flatRatePriceValueElem).closest(self.options.container).show();
            $(this.ratesCsvElem).closest(self.options.container).hide();
        } else if (ratePolicyValue === '1') { // DPDTransport::TABLE_RATE_POLICY
            $(this.flatRatePriceValueElem).closest(self.options.container).hide();
            $(this.ratesCsvElem).closest(self.options.container).show();
        }
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.$elem.off();
        this.$elem.find(this.options.countrySelector).off();

        DPDTransportSettingsComponent.__super__.dispose.call(this);
    }
});

export default DPDTransportSettingsComponent;
