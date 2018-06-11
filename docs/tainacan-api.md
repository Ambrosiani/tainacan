# Tainacan Application Programming Interface

### Brief Description

A REST API for Tainacan Plugin. This API uses the Wordpress REST API.

------
### Routes and Endpoints

#### Summary

1. [Collections](#collections)
1. [Items](#items)
1. [Metadata](#metadata)
1. [Metadatum Types](#metadatum-types)
1. [Item Metadata](#item-metadata)
1. [Taxonomies](#taxonomies)
1. [Filters](#filters)
1. [Filter Types](#filter-types)
1. [Terms](#terms)
1. [Logs](#logs)
1. [Others](#others)

------
#### Collections

1. Route `wp-json/tainacan/v2/collections/(?P<collection_id>[\d]+)`

      1. Endpoints:

            1. GET (Fetch a collection)
      
            1. DELETE (Delete or Trash a collection and all your dependencies)
            
                  *To delete pass in body of a requisition the parameter `is_permanently` as true. To only trash pass false.*

            1. PATCH or PUT (Update a collection)
    
                  Example of JSON passed in body for updating a collection:
    
```javascript
      {
            "name": "string",
            "description": "string",
            ...
      }
```

2. Route `wp-json/tainacan/v2/collections`

      1. Endpoints:
    
            1. GET (Fetch all collections)
    
            1. POST (Create a collection).
    
                  Example of JSON passed in body for creating a collection:
    
```javascript
      {
            "name": "string",
            "description": "string",
            "status": "string",
            "order": "string",
            "parent": "integer",
            "slug": "string",
            "default_orderby": "string",
            "default_order": "string",
            "columns": "string",
            "default_view_mode": "string"
      }
```

------
#### Items

1. Route `wp-json/tainacan/v2/collection/(?P<collection_id>[\d]+)/items`

      1. Endpoints:
    
            1. GET (Fetch all items from a collection)
    
            1. POST (Create a item in a collection)
    
                  Example of JSON passed in body for creating a item:

```javascript
      {
            "title": "string",
            "description": "string",
            "status": "string",
            "terms": ["integer", "integer", ...]
      }
```
    
2. Route `wp-json/tainacan/v2/items/(?P<item_id>[\d]+)`

      1. Endpoints:
    
            1. GET (Fetch a item)
    
            1. DELETE (Delete or Trash a item and all your dependencies)
    
                  *To delete pass in body of a requisition the parameter is_permanently as true. To only trash pass false.*

            1. PATCH or PUT (Update a item)
    
                  Example of JSON passed in body for updating a item:
    
```javascript
      {
            "title": "string",
            "description": "string",
            "terms": ["integer", "integer", ...]
            ...
      }
```

------
#### Metadata

1. Route `wp-json/tainacan/v2/collection/(?P<collection_id>[\d]+)/metadata`
    
      1. Endpoints:
            1. GET (Fetch all collection metadatum)
                
            1. POST (Create a metadatum in collection and all it items)
    
                  In body of requisition pass a JSON with the attributes of metadatum like:
   
```javascript
      {
            "name": "string", 
            "description": "string",
            "metadatum_type": "string",
            "order": "string",
            "parent": "integer",
            "required": "string",
            "collection_key": "string",
            "multiple": "string",
            "cardinality": "string",
            "mask": "string",
            "default_value": "string",
            "field_type_options": "string",
      }
```

2. Route `wp-json/tainacan/v2/collection/(?P<collection_id>[\d]+)/metadata/(?P<field_id>[\d]+)`

      1. Endpoints:
      
            1. GET (Fetch a metadatum from a collection or Fetch all metadatum values)
            
                  To fetch all metadatum values from a metadatum of a collection in all it items, pass a query like `?fetch=all_field_values`

            1. PATCH or PUT (Update a metadatum in a collection and all it items)

                  In body of requisition pass a JSON with the attributes you need to update, like:

```javascript
      {
            "name": "string", 
            "description": "string",
      }
```

3. Route `wp-json/tainacan/v2/metadata`

      1. Endpoints:
      
            1. GET (Fetch all default metadata)
            
            1. POST (Create a default metadatum)
            
                  In body of requisition pass a JSON with the attributes of metadatum.

4. Route `wp-json/tainacan/v2/metadata/(?P(<field_id>[\d]+))`

      1. Endpoints:
      
            1. DELETE (Trash a default metadatum)
            
            1. PATCH or PUT (Update a default metadatum)
                  
                  In body of requisition pass a JSON with the attributes you need to update.
                  
------
#### Metadatum Types
1. Route `wp-json/tainacan/v2/metadatum-types`

      1. Endpoint:
      
            1. GET (Fetch all metadatum types)
------
#### Item Metadata

1. Route `wp-json/tainacan/v2/item/(?P<item_id>[\d]+)/metadata/(?P<metadatum_id>[\d]+)`

      1. Endpoints:
    
            1. PATCH or PUT (Set value of a metadata)
    
                  In body of requisition pass a JSON with values of metadata, like:
   
```javascript
      {
            "values": ["any", "type"]
      }
```

2. Route `wp-json/tainacan/v2/item/(?P<item_id>[\d]+)/metadata`

      1. Endpoint:

            1. GET (Fetch all item metadata, with it values)

------    
#### Taxonomies

1. Route `wp-json/tainacan/v2/taxonomies`

      1. Endpoints:
    
            1. GET (Fetch all taxonomies)
    
            1. POST (Create a taxonomy)
    
                  Example of JSON passed in body for creating a taxonomy:
    
```javascript
      {
            "name": "string",
            "description": "string",
            "status": "string",
            "parent": "string",
            "slug": "string",
            "allow_insert": "string",
            "collections_ids": "array"
      }
```

2. Route `wp-json/tainacan/v2/taxonomies/(?P<taxonomy_id>[\d]+)`

      1. Endpoints:
    
            1. GET (Fetch a taxonomy)
    
            1. DELETE (Delete or trash a taxonomy)

                  *To delete pass in body of requisition the parameter is_permanently as true. To only trash pass false.*


            1. PATCH or PUT (Update a taxonomy)
    
                  Example of JSON passed in body for updating a taxonomy:
    
```javascript
      {
            "name": "string",
            "description": "string",
            ...
      }
```

3. Route `wp-json/tainacan/v2/taxonomies/(?P<taxonomy_id>[\d]+)/collection/(?P<collection_id>[\d]+)`

      1. Endpoints:

            1. PATCH or PUT (Add a Collection in a Taxonomy)

------
#### Filters

1. Route `wp-json/tainacan/v2/collection/(?P<collection_id>[\d]+)/metadatum/(?P<field_id>[\d]+)/filters`
    
      1. Endpoints:
    
            1. POST (Create a filter)
    
                  Example of JSON passed in body for creating a filter:
    
```javascript
      {
            "filter_type": "string",
            "filter": {
                  "name": "string",
                  "description": "string",
                  ...
            }
      }
```

2. Route `wp-json/tainacan/v2/filters/(?P<filter_id>[\d]+)`
    
      1. Endpoints:
    
            1. GET (Fetch a filter)
            
            1. DELETE (Delete or trash a filter)
    
                  *To delete pass in body of requisition the parameter is_permanently as true. To only trash pass false.*

            1. PATCH or PUT (Update a filter)
    
                  Example of JSON passed in body for updating a filter:
    
```javascript
      {
            "name": "string",
            ...
      }
```

3. Route `wp-json/tainacan/v2/filters`

      1. Endpoints:

            1. GET (Fetch all repository filters)
            
            1. POST (Create a filter in repository. Without metadatum and collection associations)
            
                  Example of JSON passed in body for creating a filter:
    
```javascript
      {
            "filter_type": "string",
            "filter": {
                  "name": "string",
                  "description": "string",
                  ...
            }
      }
```

4. Route `wp-json/tainacan/v2/collection/(?P<collection_id>[\d]+)/filters`

      1. Endpoints:
            
            1. GET (Fetch all collection filters)
            
            1. POST (Create a filter in a collection, without metadatum association)
            
                  Example of JSON passed in body for creating a filter:
    
```javascript
      {
            "filter_type": "string",
            "filter": {
                  "name": "string",
                  "description": "string",
                  ...
            }
      }
```  

------
#### Filter Types
1. Route `wp-json/tainacan/v2/filter-types`

      1. Endpoint:
      
            1. GET (Fetch all filter types)
------

#### Terms

1. Route `wp-json/tainacan/v2/taxonomy/(?P<taxonomy_id>[\d]+)/terms`

      1. Endpoints:
      
            1. GET (Fetch all tems of a taxonomy)
    
            1. POST (Create a term in a taxonomy)
    
                  Example of JSON passed in body for creating a term:

```javascript
      {
            "name": "string",
            "user": "int",
            ...
      }
```
   
2. Route `wp-json/tainacan/v2/taxonomy/(?P<taxonomy_id>[\d]+)/terms/(?P<term_id>[\d]+)`

      1. Endpoints:
    
            1. GET (Fecth a term of a taxonomy)
            
            1. DELETE (Delete a term of a taxonoy)
    
            1. PATCH or PUT (Update a term in a taxonomy)
    
                  Example of JSON passed in body for updating a term:
    
```javascript
      {
            "name": "string",
            ...
      }
```

------
#### Logs

1. Route `wp-json/tainacan/v2/logs`

      1. Endpoints:

            1. GET (Get all logs)

2. Route `wp-json/tainacan/v2/logs/(?P<log_id>[\d]+)`

      1. Endpoints:

            1. GET (Get a log)

------
#### Others

To Create, Read, Update or Delete Media or Users you can use the default routes of Wordpress.

See about Media in [Media | REST API Handbook](https://developer.wordpress.org/rest-api/reference/media/);

See about Users in [Users | REST API Handbook](https://developer.wordpress.org/rest-api/reference/users/).
