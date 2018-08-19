import { tainacan as axios } from '../../js/axios/axios';

export const filter_type_mixin = {
    data () {
        return {
            thumbPlaceholderPath: tainacan_plugin.base_url + '/admin/images/placeholder_square.png'
        }
    },
    props: {
        filter: {
            type: Object // concentrate all attributes metadatum id and type
        },
        metadatum_id: [Number], // not required, but overrides the filter metadatum id if is set
        collection_id: [Number], // not required, but overrides the filter metadatum id if is set
        filter_type: [String],  // not required, but overrides the filter metadatum type if is set
        id: '',
        query: {}
    },
    methods: {
        getValuesPlainText(metadatumId, search, isRepositoryLevel, valuesToIgnore, offset, number, isInCheckboxModal) {

            let url = `/collection/${this.collection}/facets/${metadatumId}`;

            if(offset != undefined && number != undefined){
                url += `?offset=${offset}&number=${number}`;
            }

            if(isRepositoryLevel){
                url = `/facets/${metadatumId}`;
            }

            if(search && offset != undefined && number != undefined){
                url += `&search=${search}`;
            } else if(search){
                url += `?search=${search}`;
            }

            return axios.get(url)
                .then(res => {
                    let sResults = [];
                    let opts = [];

                    for (let metadata of res.data) {
                        if (valuesToIgnore != undefined && valuesToIgnore.length > 0) {
                            let indexToIgnore = valuesToIgnore.findIndex(value => value == metadata.value);

                            if (search && isInCheckboxModal) {
                                sResults.push({
                                    label: metadata.label,
                                    value: metadata.value
                                });
                            } else if (indexToIgnore < 0) {
                                opts.push({
                                    label: metadata.label,
                                    value: metadata.value
                                });
                            }
                        } else {
                            if (search && isInCheckboxModal) {
                                sResults.push({
                                    label: metadata.label,
                                    value: metadata.value
                                });
                            } else {
                                opts.push({
                                    label: metadata.label,
                                    value: metadata.value
                                });
                            }
                        }


                    }


                    this.searchResults = sResults;

                    if (opts.length) {
                        this.options = opts;
                    } else if(!search) {
                        this.noMorePage = 1;
                    }

                    if(this.options.length < this.maxNumOptionsCheckboxList && !search){
                        this.noMorePage = 1;
                    }

                    if (this.filter.max_options && this.options.length >= this.filter.max_options) {
                        let seeMoreLink = `<a style="font-size: 12px;"> ${ this.$i18n.get('label_view_all') } </a>`;
                        this.options[this.filter.max_options - 1].seeMoreLink = seeMoreLink;
                    }

                })
                .catch(error => {
                    this.$console.error(error);
                });
        },
        getValuesRelationship(collectionTarget, search, valuesToIgnore, offset, number, isInCheckboxModal) {
            let url = '/collection/' + this.filter.collection_id + '/facets/' + this.filter.metadatum.metadatum_id + '?';

            if(offset != undefined && number != undefined){
                url += `offset=${offset}&perpage=${number}`;
            } else {
                url += `nopaging=1`
            }

            if(search){
                url += `&search=${search}`;
            }

            return axios.get(url + '&fetch_only[0]=thumbnail&fetch_only[1]=title&fetch_only[2]=id')
                .then(res => {
                    let sResults = [];
                    let opts = [];

                    if (res.data.length > 0) {
                        for (let item of res.data) {
                            if (valuesToIgnore != undefined && valuesToIgnore.length > 0) {
                                let indexToIgnore = valuesToIgnore.findIndex(value => value == item.value);

                                if (search && isInCheckboxModal) {
                                    sResults.push({
                                        label: item.label,
                                        value: item.value
                                    });
                                } else if (indexToIgnore < 0) {
                                    opts.push({
                                        label: item.label,
                                        value: item.value,
                                        img: (item.img ? item.img : this.thumbPlaceholderPath)
                                    });
                                }
                            } else {
                                if (search && isInCheckboxModal) {
                                    sResults.push({
                                        label: item.label,
                                        value: item.value,
                                        img: (item.img ? item.img : this.thumbPlaceholderPath)
                                    });
                                } else {
                                    opts.push({
                                        label: item.label,
                                        value: item.value,
                                        img: (item.img ? item.img : this.thumbPlaceholderPath)
                                    });
                                }
                            }
                        }
                    }

                    this.searchResults = sResults;

                    if (opts.length) {
                        this.options = opts;
                    } else {
                        this.noMorePage = 1;
                    }

                    if(this.options.length < this.maxNumOptionsCheckboxList){
                        this.noMorePage = 1;
                    }

                    if (this.filter.max_options && this.options.length >= this.filter.max_options) {
                        let seeMoreLink = `<a style="font-size: 12px;"> ${ this.$i18n.get('label_view_all') } </a>`;
                        this.options[this.filter.max_options - 1].seeMoreLink = seeMoreLink;
                    }

                })
                .catch(error => {
                    this.$console.error(error);
                });
        }
    }
};