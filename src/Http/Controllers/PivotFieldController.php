<?php

namespace Armincms\Fields\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;

class PivotFieldController extends Controller
{
    use ConditionallyLoadsAttributes, InteractsWithResourceRequest, ResolvesFields;

    /**
     * List the pivot fields for the given resource and relation.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function index(NovaRequest $request)
    {   
        return response()->json($this->fields($request, $this->resource($request))->all());
    } 
}
