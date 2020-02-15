<?php

namespace Armincms\Fields\Http\Controllers;

use Illuminate\Routing\Controller;
use Armincms\Fields\BelongsToMany;
use Armincms\Fields\MorphToMany;
use Laravel\Nova\Http\Requests\NovaRequest;

class AttachableController extends Controller
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

        $attachedResources = collect($resource->{$field->manyToManyRelationship})->map->getKey();
 
        $withTrashed = $this->shouldIncludeTrashed(
            $request, $associatedResource = $field->resourceClass
        );
 
        return $field->buildAttachableQuery($request, $withTrashed)->get()
                    ->mapInto($associatedResource) 
                    ->map(function ($resource) use ($request, $field, $attachedResources) { 
                        $key = $resource->resource->getKey();

                        return array_merge([
                            'text'  => $key,
                            'attached' => $attachedResources->contains($key),
                        ], $field->formatAttachableResource($request, $resource)); 
                    })->sortBy('display', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Determine if the query should include trashed models.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $associatedResource
     * @return bool
     */
    protected function shouldIncludeTrashed(NovaRequest $request, $associatedResource)
    {
        if ($request->withTrashed === 'true') {
            return true;
        }

        $associatedModel = $associatedResource::newModel();

        if ($request->current && $associatedResource::softDeletes()) {
            $associatedModel = $associatedModel->newQueryWithoutScopes()->find($request->current);

            return $associatedModel ? $associatedModel->trashed() : false;
        }

        return false;
    }
}
