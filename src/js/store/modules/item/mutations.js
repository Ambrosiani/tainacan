import Vue from 'vue';

export const setItem = ( state, item ) => {
    state.item = item;
}

export const setFields = ( state, field) => {
    state.fields = field;
}

export const setSingleField = ( state, field) => {
    let index = state.fields.findIndex(itemMetadata => itemMetadata.field.id === field.field.id);
    if ( index >= 0){
        //state.field[index] = field;
        Vue.set( state.fields, index, field );
    }else{
        state.fields.push( field );
    }
}

export const setError = ( state, field ) => {
    let index = state.error.findIndex(itemMetadata => itemMetadata.field_id === field.field_id);
    if ( index >= 0){
        //state.error[index] = field;
        Vue.set( state.error, index, field );
    }else{
        state.error.push( field );
    }
};

export const removeError =  ( state, field ) => {
    let index = state.error.findIndex(itemMetadata => itemMetadata.field_id === field.field_id);
    if ( index >= 0){
        state.error.splice( index, 1);
    }
}