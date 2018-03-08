import * as actions from './actions';
import * as getters from './getters';
import * as mutations from './mutations';

const state = {
    item: [],
    fields: [],
    error: [],
    itemTitle: ''
};


export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}