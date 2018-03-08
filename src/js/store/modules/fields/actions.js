import axios from '../../../axios/axios';
import qs from 'qs';

export const fetchFields = ({ commit }, {collectionId, isRepositoryLevel}) => {
    return new Promise((resolve, reject) => {
        let endpoint = '';
        if (!isRepositoryLevel) 
            endpoint = '/collection/' + collectionId + '/fields/'; 
        else
            endpoint = '/fields/';

        axios.get(endpoint + '?context=edit')
        .then((res) => {
            let fields= res.data;
            commit('setFields', fields);
            resolve (fields);
        }) 
        .catch((error) => {
            console.log(error);
            reject(error);
        });
    });
}


export const fetchField = ({ commit }, {collectionId, fieldId, isRepositoryLevel}) => {
    return new Promise((resolve, reject) => {

        let endpoint = '';
        if (!isRepositoryLevel) 
            endpoint = '/collection/' + collectionId + '/fields/' + fieldId; 
        else
            endpoint = '/fields/' + fieldId;

        axios.get(endpoint + '?context=edit')
        .then((res) => {
            let field = res.data;
            commit('setSingleField', field);
            resolve (field);
        }) 
        .catch((error) => {
            console.log(error);
            reject(error);
        });
    });
}


export const sendField = ( { commit }, { collectionId, name, fieldType, status, isRepositoryLevel }) => {
    return new Promise(( resolve, reject ) => {
        let endpoint = '';
        if (!isRepositoryLevel) 
            endpoint = '/collection/' + collectionId + '/fields/'; 
        else
            endpoint = '/fields/';
        axios.post(endpoint + '?context=edit', {
            name: name,
            field_type: fieldType, 
            status: status
        })
            .then( res => {
                commit('setField', { collection_id: collectionId, name: name, field_type: fieldType, status: status });
                resolve( res.data );
            })
            .catch(error => {
                reject( error.response );
            });
    });
};

export const updateField = ( { commit }, { collectionId, fieldId, isRepositoryLevel, options }) => {
    return new Promise(( resolve, reject ) => {
        let endpoint = '';

        if (!isRepositoryLevel) 
            endpoint = '/collection/' + collectionId + '/fields/' + fieldId; 
        else
            endpoint = '/fields/' + fieldId;

        axios.put(endpoint, options)
            .then( res => {
                commit('setSingleField', res.data);
                resolve( res.data );
            })
            .catch(error => {
                console.log(error);
                reject({ error_message: error['response']['data'].error_message, errors: error['response']['data'].errors });
            });
    });
};


export const deleteField = ({ commit }, { collectionId, fieldId, isRepositoryLevel }) => {
    let endpoint = '';
    if (!isRepositoryLevel) 
        endpoint = '/collection/' + collectionId + '/fields/' + fieldId; 
    else
        endpoint = '/fields/' + fieldId;

    return new Promise((resolve, reject) => {
        axios.delete(endpoint)
        .then( res => {
            commit('deleteField', { fieldId } );
            resolve( res.data );
        }).catch((error) => { 
            console.log(error);
            reject( error );
        });

    }); 
};

export const updateCollectionFieldsOrder = ({ commit }, { collectionId, fieldsOrder }) => {
    return new Promise((resolve, reject) => {
        axios.patch('/collections/' + collectionId, {
            fields_order: fieldsOrder
        }).then( res => {
            commit('setCollection', res.data);
            resolve( res.data );
        }).catch( error => { 
            reject( error.response );
        });

    });
}

 export const fetchFieldTypes = ({ commit} ) => {
    return new Promise((resolve, reject) => {
        axios.get('/field-types')
        .then((res) => {
            let fieldTypes = res.data;
            commit('setFieldTypes', fieldTypes);
            resolve (fieldTypes);
        })
        .catch((error) => {
            console.log(error);
            reject(error);
        });
    });
}
