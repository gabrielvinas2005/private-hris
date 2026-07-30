<?php

namespace App\Support;

/**
 * Employee pickers for Payroll Benefits: plantilla only, no end-contract employment types.
 */
class PayrollBenefitsEmployeeScope
{
    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    public static function apply($query, string $alias = 'employees')
    {
        return $query
            ->where(function ($q) use ($alias) {
                $q->where("{$alias}.is_plantilla", true)
                    ->orWhere("{$alias}.is_plantilla", 1)
                    ->orWhere("{$alias}.is_plantilla", 'true');
            })
            ->whereIn("{$alias}.employment_type_id", function ($sub) {
                $sub->select('id')
                    ->from('employment_types')
                    ->where(function ($q) {
                        $q->where('with_end_contract', false)
                            ->orWhere('with_end_contract', 0)
                            ->orWhereNull('with_end_contract');
                    });
            });
    }
}
