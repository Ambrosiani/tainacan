import tainacan from '../../api-client/axios.js';
import axios from 'axios';

const { __ } = wp.i18n;

const { TextControl, Button, Modal, RadioControl, SelectControl, Spinner } = wp.components;

export default class ParentTermModal extends React.Component {
    constructor(props) {
        super(props);

        // Initialize state
        this.state = {
            metadatumId: '',
            facetsPerPage: 24,
            facetId: undefined,
            isLoadingFacets: false, 
            modalFacets: [],
            totalModalFacets: 0, 
            facetPage: 1,
            temporaryFacetId: '',
            searchFacetName: '',
            facetOrderBy: 'date-desc',
            facets: [],
            facetsRequestSource: undefined
        };
        
        // Bind events
        this.selectFacet = this.selectFacet.bind(this);
        this.fetchFacets = this.fetchFacets.bind(this);
        this.fetchModalFacets = this.fetchModalFacets.bind(this);
    }

    componentWillMount() {
        this.setState({
            collectionId: this.props.collectionId,
            metadatumId: this.props.metadatumId, 
            temporaryFacetId: this.props.existingFacetId,
            facetId: this.props.existingFacetId,
            facetPage: 1 
        });
        this.fetchModalFacets();
    }

    // COLLECTIONS RELATED --------------------------------------------------
    fetchModalFacets() {

        let someModalFacets = this.state.modalFacets;
        if (this.state.facetPage <= 1)
            someModalFacets = [];

        let endpoint = '/facets/' + this.props.metadatumId + '?number=' + this.state.facetsPerPage + '&paged=' + this.state.facetPage;

        if (this.state.collectionId)
            endpoint = '/collection/' + this.props.collectionId + endpoint;

        if (this.state.facetOrderBy == 'date')
            endpoint += '&orderby=date&order=asc';
        else if (this.state.facetOrderBy == 'date-desc')
            endpoint += '&orderby=date&order=desc';
        else if (this.state.facetOrderBy == 'title')
            endpoint += '&orderby=title&order=asc';
        else if (this.state.facetOrderBy == 'title-desc')
            endpoint += '&orderby=title&order=desc';

        this.setState({ 
            isLoadingFacets: true,
            facetPage: this.state.facetPage + 1, 
            modalFacets: someModalFacets
        });

        tainacan.get(endpoint)
            .then(response => {

                let otherModalFacets = this.state.modalFacets;

                for (let facet of response.data.values) {
                    otherModalFacets.push({ 
                        name: facet.label, 
                        id: facet.value
                    });
                }

                this.setState({ 
                    isLoadingFacets: false, 
                    modalFacets: otherModalFacets,
                    totalModalFacets: response.headers['x-wp-total']
                });
            
                return otherModalFacets;
            })
            .catch(error => {
                console.log('Error trying to fetch facets: ' + error);
            });
    }

    selectFacet(selectedFacetId) {

        let selectedFacet;
        if (selectedFacetId === null || selectedFacetId === '')
            selectedFacet = { name: __('Any term.', 'tainacan'), id: null };
        else if (selectedFacetId == '0' || selectedFacet == 0)
            selectedFacet = { name: __('Root terms', 'tainacan'), id: '0' };
        else {
            selectedFacet = this.state.modalFacets.find((facet) => facet.id == selectedFacetId)
            if (selectedFacet == undefined)
                selectedFacet = this.state.facets.find((facet) => facet.id == selectedFacetId)
        }

        this.setState({
            facetId: selectedFacet.id   
        });

        this.props.onSelectFacet(selectedFacet);

    }

    fetchFacets(name) {

        if (this.state.facetsRequestSource != undefined)
            this.state.facetsRequestSource.cancel('Previous facets search canceled.');

        let aFacetRequestSource = axios.CancelToken.source();

        this.setState({ 
            facetsRequestSource: aFacetRequestSource,
            isLoadingFacets: true, 
            facets: [],
            metadata: []
        });

        let endpoint = '/facets/' + this.props.metadatumId + '?number=' + this.state.facetsPerPage;

        if (this.state.collectionId)
            endpoint = '/collection/' + this.props.collectionId + endpoint;

        if (name != undefined && name != '')
            endpoint += '&search=' + name;

        if (this.state.facetOrderBy == 'date')
            endpoint += '&orderby=date&order=asc';
        else if (this.state.facetOrderBy == 'date-desc')
            endpoint += '&orderby=date&order=desc';
        else if (this.state.facetOrderBy == 'title')
            endpoint += '&orderby=title&order=asc';
        else if (this.state.facetOrderBy == 'title-desc')
            endpoint += '&orderby=title&order=desc';

        tainacan.get(endpoint, { cancelToken: aFacetRequestSource.token })
            .then(response => {
                let someFacets = response.data.values.map((facet) => ({ name: facet.label, id: facet.value + '' }));

                this.setState({ 
                    isLoadingFacets: false, 
                    facets: someFacets
                });
                
                return someFacets;
            })
            .catch(error => {
                console.log('Error trying to fetch facets: ' + error);
            });
    }

    cancelSelection() {

        this.setState({
            modalFacets: []
        });

        this.props.onCancelSelection();
    }

    render() {
        return (
        
        // Facets modal
        <Modal
                className="wp-block-tainacan-modal"
                title={__('Select a parent term to fetch facets from', 'tainacan')}
                onRequestClose={ () => this.cancelSelection() }
                contentLabel={__('Select term', 'tainacan')}>
                <div>
                    <div className="modal-search-area">
                        <TextControl 
                                label={__('Search for a term', 'tainacan')} 
                                placeholder={ __('Search by term\'s name', 'tainacan') }
                                value={ this.state.searchFacetName }
                                onChange={(value) => {
                                    this.setState({ 
                                        searchFacetName: value
                                    });
                                    _.debounce(this.fetchFacets(value), 300);
                                }}/>
                        <SelectControl
                                label={__('Order by', 'tainacan')}
                                value={ this.state.facetOrderBy }
                                options={ [
                                    { label: __('Latest', 'tainacan'), value: 'date-desc' },
                                    { label: __('Oldest', 'tainacan'), value: 'date' },
                                    { label: __('Name (A-Z)', 'tainacan'), value: 'title' },
                                    { label: __('Name (Z-A)', 'tainacan'), value: 'title-desc' }
                                ] }
                                onChange={ ( aFacetOrderBy ) => { 
                                    this.state.facetOrderBy = aFacetOrderBy;
                                    this.state.facetPage = 1;
                                    this.setState({ 
                                        facetOrderBy: this.state.facetOrderBy,
                                        facetPage: this.state.facetPage 
                                    });
                                    if (this.state.searchFacetName && this.state.searchFacetName != '') {
                                        this.fetchFacets(this.state.searchFacetName);
                                    } else {
                                        this.fetchModalFacets();
                                    }
                                }}/>
                    </div>
                    {(
                    this.state.searchFacetName != '' ? (
                        this.state.facets.length > 0 ?
                        (
                            <div>
                                <div className="modal-radio-list">
                                    {  
                                    <RadioControl
                                        selected={ this.state.temporaryFacetId }
                                        options={
                                            this.state.facets.map((facet) => {
                                                return { label: facet.name, value: '' + facet.id }
                                            })
                                        }
                                        onChange={ ( aFacetId ) => { 
                                            this.setState({ temporaryFacetId: aFacetId });
                                        } } />
                                    }                                      
                                </div>
                                <br/>
                            </div>
                        ) :
                        this.state.isLoadingFacets ? (
                            <Spinner />
                        ) :
                        <div className="modal-loadmore-section">
                            <p>{ __('Sorry, no term found.', 'tainacan') }</p>
                        </div> 
                    ):
                    this.state.modalFacets.length > 0 ? 
                    (   
                        <div>
                            <div className="modal-radio-list">
                                
                                <p class="modal-radio-area-label">{__('Any parent term', 'tainacan')}</p>
                                <RadioControl
                                    className={'repository-radio-option'}
                                    selected={ this.state.temporaryFacetId }
                                    options={ [
                                        { label: __('Fetch terms children of any term', 'tainacan'), value: null }, 
                                        { label: __('Fetch terms with no parent (root terms)', 'tainacan'), value: '0' }
                                    ] }
                                    onChange={ ( aFacetId ) => {
                                        this.setState({ temporaryFacetId: aFacetId });
                                    } } />
                                <hr/>
                                <p class="modal-radio-area-label">{__('Terms', 'tainacan')}</p>
                                <RadioControl
                                    selected={ this.state.temporaryFacetId }
                                    options={
                                        this.state.modalFacets.map((facet) => {
                                            return { label: facet.name, value: '' + facet.id }
                                        })
                                    }
                                    onChange={ ( aFacetId ) => { 
                                        this.setState({ temporaryFacetId: aFacetId });
                                    } } />                          
                            </div>
                            <div className="modal-loadmore-section">
                                <p>{ __('Showing', 'tainacan') + " " + this.state.modalFacets.length + " " + __('of', 'tainacan') + " " + this.state.totalModalFacets + " " + __('terms', 'tainacan') + "."}</p>
                                {
                                    this.state.modalFacets.length < this.state.totalModalFacets ? (
                                    <Button 
                                        isDefault
                                        isSmall
                                        onClick={ () => this.fetchModalFacets() }>
                                        {__('Load more', 'tainacan')}
                                    </Button>
                                    ) : null
                                }
                            </div>
                        </div>
                    ) : this.state.isLoadingFacets ? <Spinner/> :
                    <div className="modal-loadmore-section">
                        <p>{ __('Sorry, no terms found.', 'tainacan') }</p>
                    </div>
                )}
                <div className="modal-footer-area">
                    <Button 
                        isDefault
                        onClick={ () => { this.cancelSelection() }}>
                        {__('Cancel', 'tainacan')}
                    </Button>
                    <Button
                        isPrimary
                        disabled={ this.state.temporaryFacetId == undefined || this.state.temporaryFacetId == null || this.state.temporaryFacetId == '' && (this.state.searchFacetName != '' ? this.state.facets.find((facet) => facet.id == this.state.temporaryFacetId) : this.state.modalFacets.find((facet) => facet.id == this.state.temporaryFacetId)) != undefined}
                        onClick={ () => { this.selectFacet(this.state.temporaryFacetId) } }>
                        {__('Select term', 'tainacan')}
                    </Button>
                </div>
            </div>
        </Modal> 
        );
    }
}