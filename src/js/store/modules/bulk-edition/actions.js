import axios from '../../../axios/axios';

export const createEditGroup = ({commit}, parameters) => {
    let object = parameters.object;
    let collectionID = parameters.collectionID;

    let bulkEditParams = null;

    if(object.constructor.name === 'Array'){
        bulkEditParams = {
            items_ids: object,
        };
    } else if(object.constructor.name === 'Object'){
        bulkEditParams = {
            use_query: object,
        };
    }

    return axios.tainacan.post(`/collection/${collectionID}/bulk-edit`, bulkEditParams)
        .then(response => {
            commit('setGroup', response.data);
        })
        .catch(error => {
            console.error(error);
        })
};

export const setValueInBulk = ({commit}, parameters) => {
    let groupID = parameters.groupID;
    let collectionID = parameters.collectionID;

    /**
     * @var bodyParams { metadatum_id, new_value } Object
     * */
    let bodyParams = parameters.bodyParams;

    return axios.tainacan.post(`/collection/${collectionID}/bulk-edit/${groupID}/set`, bodyParams)
        .then(response => {
            commit('setActionResult', response.data);
        })
        .catch(error => {
            console.error(error);
        });
};

export const addValueInBulk = ({commit}, parameters) => {
    let groupID = parameters.groupID;
    let collectionID = parameters.collectionID;

    /**
     * @var bodyParams { metadatum_id, new_value } Object
     * */
    let bodyParams = parameters.bodyParams;

    return axios.tainacan.post(`/collection/${collectionID}/bulk-edit/${groupID}/add`, bodyParams)
        .then(response => {
            commit('setActionResult', response.data);
        })
        .catch(error => {
            console.error(error);
        });
};

export const removeValueInBulk = ({commit}, parameters) => {
    let groupID = parameters.groupID;
    let collectionID = parameters.collectionID;

    /**
     * @var bodyParams { metadatum_id, new_value } Object
     * */
    let bodyParams = parameters.bodyParams;

    return axios.tainacan.post(`/collection/${collectionID}/bulk-edit/${groupID}/remove`, bodyParams)
        .then(response => {
            commit('setActionResult', response.data);
        })
        .catch(error => {
            console.error(error);
        });
};

export const replaceValueInBulk = ({commit}, parameters) => {
    let groupID = parameters.groupID;
    let collectionID = parameters.collectionID;

    /**
     * @var bodyParams { metadatum_id, old_value, new_value } Object
     * */
    let bodyParams = parameters.bodyParams;

    return axios.tainacan.post(`/collection/${collectionID}/bulk-edit/${groupID}/replace`, bodyParams)
        .then(response => {
            commit('setActionResult', response.data);
        })
        .catch(error => {
            console.error(error);
        });
};

export const setStatusInBulk = ({commit}, parameters) => {
    let groupID = parameters.groupID;
    let collectionID = parameters.collectionID;

    /**
     * The new status value (draft, publish or private)
     * @var bodyParams String
     * */
    let bodyParams = parameters.bodyParams;

    return axios.tainacan.post(`/collection/${collectionID}/bulk-edit/${groupID}/set_status`, bodyParams)
        .then(response => {
            commit('setActionResult', response.data);
        })
        .catch(error => {
            console.error(error);
        });
};