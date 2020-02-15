<?php

namespace Armincms\Fields\Http\Controllers;

use Illuminate\Routing\Controller;
use Armincms\Fields\BelongsToMany;
use Armincms\Fields\MorphToMany;
use Laravel\Nova\Http\Requests\NovaRequest;

class AttachedController extends Controller
{
    use InteractsWithResourceRequest, ResolvesFields;
    
    /**
     * List the available related resources for a given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function handle(NovaRequest $request)
    { 
        $field = $this->relatedFieldFor(
            $request, $resource = $this->resource($request), $request->field
        );    

        $relationship = $resource->{$field->manyToManyRelationship}();
        $accessor = $relationship->getPivotAccessor();


        return $relationship->withPivot('id')->get()
                    ->mapInto($field->resourceClass)
                    ->map(function($relatedResource) use ($field, $request, $accessor) {  
                        return array_merge([
                            'pivotId'   => $relatedResource->{$accessor}->id,
                            'attached'  => true,
                        ], $field->formatAttachableResource($request, $relatedResource));
                    })->values();  
    } 
}
