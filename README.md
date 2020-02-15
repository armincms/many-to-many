# BelongsToMany
A Laravel Nova field for `simple` and `polymorphic` `ManyToMany` relationships.

##### Table of Contents   
* [Features](#features)      
* [Install](#install)      
* [Simple Usage](#simple-usage)        
* [Pivots](#pivots)          
* [Duplicate Attachment](#duplicate-attachment)          
* [Polymorphic Relation](#polymorphic-relation)          


## Features
  - Attach polymorphic and non-polymorphic `ManyToMany` relationships in 
      the creation and update page
  - Edit pivot columns when attaching relation
  - Attach a source to another resource many times

## Install
```bash
composer require armincms/belongs-to-many
``` 
  
## Simple Usage  

```  
  use Armincms\Fields\BelongsToMany;  
  

  
  /**
   * Get the fields displayed by the resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function fields(Request $request)
  {
    return [
      BelongsToMany::make(__("Label"), 'relationName', RelatedResource::class)
          ->fields(function() {
              return [
                  Text::make('Price')
                          ->rules('required', 'numeric'),   
              ];
          })
          ->pivots(),
    ];
  }

```

## Pivots 
For customizing the pivot columns when attaching a resource you can use the `pivots` method of the field. then define your custom pivot fields with the `fields` method. now, when attaching a resource; a Modal that contains the pivot fields will be displayed to you.


```  
  use Armincms\Fields\BelongsToMany;  
  

  
  /**
   * Get the fields displayed by the resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function fields(Request $request)
  {
    return [
      BelongsToMany::make(__("Label"), 'relationName', RelatedResource::class)
          ->fields(function() {
              return [
                  Text::make('Price')
                          ->rules('required', 'numeric'),   
              ];
          })
          ->pivots(),
    ];
  }

```
 
## Duplicate Attachment 
You can use the `duplicate` feature for repetitively attach a resource to another resource. follow the example:



```  
  use Armincms\Fields\BelongsToMany;  
  

  
  /**
   * Get the fields displayed by the resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function fields(Request $request)
  {
    return [
      BelongsToMany::make(__("Label"), 'relationName', RelatedResource::class)
          ->fields(function() {
              return [
                  Text::make('Price')
                          ->rules('required', 'numeric'),   
              ];
          })
          ->duplicate(),
    ];
  }

```
## Polymorphic Relation
Using for the polymorphic relationships is like non-polymorphic. follow the example:


```  
  use Armincms\Fields\MorphToMany;  
  

  
  /**
   * Get the fields displayed by the resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function fields(Request $request)
  {
    return [
      MorphToMany::make(__("Label"), 'relationName', RelatedResource::class)
          ->fields(function() {
              return [
                  Text::make('Price')
                          ->rules('required', 'numeric'),   
              ];
          })
          ->duplicate()
          ->pivots(),
    ];
  }

```

or

```  
  use Armincms\Fields\MorphedByMany;  
  

  
  /**
   * Get the fields displayed by the resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function fields(Request $request)
  {
    return [
      MorphedByMany::make(__("Label"), 'relationName', RelatedResource::class)
          ->fields(function() {
              return [
                  Text::make('Price')
                          ->rules('required', 'numeric'),   
              ];
          })
          ->duplicate()
          ->pivots(),
    ];
  }

```