export const getPostQuery = state => {
    return state.postquery;
}

export const getMetaQuery = state => {
    return state.metaquery;
}

export const getTaxQuery = state => {
    return state.taxquery;
}

export const getTotalItems = state => {
    return state.totalItems;
}

export const getPage = state => {
    return state.postquery.paged;
}

export const getItemsPerPage = state => {
    return state.postquery.perpage;
};

export const getOrder = state => {
    return state.postquery.order;
}

export const getOrderBy = state => {
    return state.postquery.orderby;
};

export const getSearchQuery = state => {
    return state.postquery.search;
}

export const getFecthOnly = state => {
    return state.postquery.fetch_only;
}

export const getFecthOnlyMeta = state => {
    return ( ! state.postquery.fetch_only['meta'] ) ? [] : state.postquery.fetch_only['meta'];
}