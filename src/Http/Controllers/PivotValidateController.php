<?php

namespace Armincms\Fields\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Support\Facades\Validator;

class PivotValidateController extends Controller
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
        $rules = [];

        $ruleCallback = function($field) use ($request, &$rules) {

            $rules = array_merge_recursive(
                $rules, 
                $request->isCreateOrAttachRequest() 
                    ? $field->getCreationRules($request) 
                    : $field->getUpdateRules($request)
            ); 

        };

        $this->fields($request, $this->resource($request))->each($ruleCallback); 

        Validator::make($request->all(), $rules)->validate();

        return response()->json();
    } 
}
