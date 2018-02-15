import axios from '../../../axios/axios';

// Actions related to Item's field
export const sendField = ( { commit }, { item_id, field_id, values }) => {
   return new Promise( (resolve, reject) => {
       axios.post('/item/'+item_id+'/metadata/'+field_id, {
           values: values
       })
           .then( res => {
               commit('setSingleMetadata', { item_id: item_id, field_id: field_id, values: values });
               resolve( res.data );
           })
           .catch(error => {
               reject( error);
           });
   });
};


export const updateMetadata = ({ commit }, { item_id, field_id, values, old_values }) => {
    return new Promise((resolve, reject) => {
        
        let metadata_values = [];

        // values = ["a", "b", "c"]
        // old_values = ["a", "b"]
        if (values.lenght >= old_values.length) {
            // New values in metadata array
            for (let i = 0; i < values.length; i++) {
                if (old_values[i])
                    metadata_values.push({"new": values[i], "prev": old_values[i]});
                else
                    metadata_values.push({"new": values[i], "prev": null });
            }
        }
        // values = ["a", "c"]
        // old_values = ["a", "b", "c"]
        else {
            // Values removed from metadata array
            for (let i = 0; i < old_values.length; i++) {
                if (values[i])
                    metadata_values.push({"new": values[i], "prev": old_values[i]});
                else
                    metadata_values.push({"new": null, "prev": old_values[i]});
            }
        }        
        //console.log(metadata_values);
        axios.patch(`/item/${item_id}/metadata/${field_id}`, {
            values: metadata_values,
        })
            .then( res => {
                let field = res.data;
                commit('setSingleField', field);
                resolve(field)
            })
            .catch( error => {
                reject(error.response.data.errors);
            })
    });
};

export const fetchFields = ({ commit }, item_id) => {
    return new Promise((resolve, reject) => {
        axios.get('/item/'+item_id+'/metadata')
        .then(res => {
            let items = res.data;
            commit('setFields', items);
            resolve( res.data );
        })
        .catch(error => {
            reject( error );
        });
    });
};

// Actions directly related to Item
export const fetchItem = ({ commit }, item_id) => {
    return new Promise((resolve, reject) => {
        axios.get('/items/'+item_id)
        .then(res => {
            let item = res.data;
            commit('setItem', item);
            resolve( res.data );
        })
        .catch(error => {
            reject( error );
        });
    });
};

export const sendItem = ( { commit }, { collection_id, title, description, status }) => {
    return new Promise(( resolve, reject ) => {
        axios.post('/collection/'+ collection_id + '/items/', {
            title: title,
            description: description,
            status: status
        })
            .then( res => {
                commit('setItem', { collection_id: collection_id, title: title, description: description, status: status });
                resolve( res.data );
            })
            .catch(error => {
                reject( error.response );
            });
    });
 };
 
 
 export const updateItem = ({ commit }, { item_id, title, description, status }) => {
    return new Promise((resolve, reject) => {
        axios.patch('/items/' + item_id, {
            title: title,
            description: description,
            status: status 
        }).then( res => {
            commit('setItem', { id: item_id, title: title, description: description, status: status });
            resolve( res.data );
        }).catch( error => { 
            reject( error.response );
        });

    });
};