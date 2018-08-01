// Overrides lodash by original WordPress Underscore Library
//window.lodash = _.noConflict();
//window.underscore = _.noConflict();

// Main imports
import Vue from 'vue';
import Buefy from 'buefy';
import VTooltip from 'v-tooltip';
import { VueHammer } from 'vue2-hammer';

// Custom elements
import Text from '../../classes/metadata-types/text/Text.vue';
import Textarea from '../../classes/metadata-types/textarea/Textarea.vue';
import Selectbox from '../../classes/metadata-types/selectbox/Selectbox.vue';
import Numeric from '../../classes/metadata-types/numeric/Numeric.vue';
import Date from '../../classes/metadata-types/date/Date.vue';
import Relationship from '../../classes/metadata-types/relationship/Relationship.vue';
import Taxonomy from '../../classes/metadata-types/taxonomy/Taxonomy.vue';

import FormRelationship from '../../classes/metadata-types/relationship/FormRelationship.vue';
import FormTaxonomy from '../../classes/metadata-types/taxonomy/FormTaxonomy.vue';
import FormSelectbox from '../../classes/metadata-types/selectbox/FormSelectbox.vue';

import FilterCustomInterval from '../../classes/filter-types/custom-interval/CustomInterval.vue';
import FilterSelectbox from '../../classes/filter-types/selectbox/Selectbox.vue';
import FilterAutocomplete from '../../classes/filter-types/autocomplete/Autocomplete.vue';
import FilterCheckbox from '../../classes/filter-types/checkbox/Checkbox.vue';
import FilterTaginput from '../../classes/filter-types/taginput/Taginput.vue';

import FilterTaxonomyCheckbox from '../../classes/filter-types/taxonomy/Checkbox.vue';
import FilterTaxonomyTaginput from '../../classes/filter-types/taxonomy/Taginput.vue';
import FilterTaxonomySelectbox from '../../classes/filter-types/taxonomy/Selectbox.vue';

import TainacanFormItem from '../../classes/metadata-types/tainacan-form-item.vue';
import TainacanFiltersList from '../../classes/filter-types/tainacan-filter-item.vue';

// Remaining imports
import AdminPage from '../admin.vue'
import HelpButton from '../components/other/help-button.vue';
import TainacanTitle from '../components/navigation/tainacan-title.vue';
import draggable from 'vuedraggable'
import store from '../../js/store/store'
import router from './router'
import eventBusSearch from '../../js/event-bus-search';
import { I18NPlugin, UserPrefsPlugin, RouterHelperPlugin, ConsolePlugin, UserCapabilitiesPlugin } from './utilities';
import VueTheMask from 'vue-the-mask';

// Configure and Register Plugins
Vue.use(Buefy);
Vue.use(VTooltip);
Vue.use(VueHammer);
Vue.use(I18NPlugin);
Vue.use(UserPrefsPlugin);
Vue.use(RouterHelperPlugin);
Vue.use(UserCapabilitiesPlugin);
Vue.use(ConsolePlugin, {visual: false});
Vue.use(VueTheMask);

/* Metadata */
Vue.component('tainacan-text', Text);
Vue.component('tainacan-textarea', Textarea);
Vue.component('tainacan-selectbox', Selectbox);
Vue.component('tainacan-numeric', Numeric);
Vue.component('tainacan-date', Date);
Vue.component('tainacan-relationship', Relationship);
Vue.component('tainacan-taxonomy', Taxonomy);

Vue.component('tainacan-form-relationship', FormRelationship);
Vue.component('tainacan-form-taxonomy', FormTaxonomy);
Vue.component('tainacan-form-selectbox', FormSelectbox);
Vue.component('tainacan-form-item', TainacanFormItem);
Vue.component('tainacan-filter-item', TainacanFiltersList);

/* Filters */
Vue.component('tainacan-filter-custom-interval', FilterCustomInterval);
Vue.component('tainacan-filter-selectbox', FilterSelectbox);
Vue.component('tainacan-filter-autocomplete', FilterAutocomplete);
Vue.component('tainacan-filter-checkbox', FilterCheckbox);
Vue.component('tainacan-filter-taginput', FilterTaginput);
Vue.component('tainacan-filter-taxonomy-checkbox', FilterTaxonomyCheckbox);
Vue.component('tainacan-filter-taxonomy-taginput', FilterTaxonomyTaginput);
Vue.component('tainacan-filter-taxonomy-selectbox', FilterTaxonomySelectbox);

/* Others */
Vue.component('help-button', HelpButton);
Vue.component('draggable', draggable);
Vue.component('tainacan-title', TainacanTitle);

Vue.use(eventBusSearch, { store: store, router: router});

// Changing title of pages
router.beforeEach((to, from, next) => {
    document.title = to.meta.title;
    next();
});

new Vue({
    el: '#tainacan-admin-app',
    store,
    router,
    render: h => h(AdminPage)
});