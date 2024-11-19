<?php

namespace App\Http\Controllers;

use App\Models\Funder;
use App\Models\Property;
use App\Models\Rent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    // get wallet details
    public function wallet()
    {
        $user = auth()->user();
        $receipts = $user->receipts;
        $receipt_not_rejected = $receipts->where('status', '!=', 'rejected');

        //number of shares
        $shares_funder = Funder::where(['user_id'=> $user_id,'status'=>'funder'])->get();
        



        

        // my investment
        $investment = 0;
        foreach ($receipt_not_rejected as $receipt) {
            $sheres_count = $receipt->count_sheres;
            $property = Property::find($receipt->property_id);
            $receipt_price = $property->property_price / $property->funder_count * $sheres_count;
            $investment += $receipt_price;
        }

        // deposit
        $receipt_pending = $receipts->where('status', 'pending');
        $deposit = 0;
        foreach ($receipt_pending as $receipt) {
            $sheres_count = $receipt->count_sheres * 5000;
            // $property = Property::find($receipt->property_id);
            // $receipt_price = $property->property_price * $sheres_count;
            $deposit += $sheres_count;
        }

        // number of properties
        $properties =  $this->getPropOfFunders('', $user);

        // monthly income
        $monthly_income = 0;
        $properties_of_monthly_income =  $this->getPropOfFunders('funder', $user);

        foreach ($properties_of_monthly_income as $prop) {
            $rent = $prop->rents->where('status', 'active')->first();
            if ($rent) {
                $date = Carbon::parse($rent->end_date);
                if ($date->isPast()) {
                    $count_shere = Funder::where(['status' => 'funder', 'user_id' => $user->id, 'property_id' => $prop->id])->count();
                    $rent_active_naw = Rent::where(['property_id' => $prop->id, 'status' => 'active'])->first();
                    $monthly_income += ($rent_active_naw->monthly_income / $prop->funder_count * $count_shere);
                }
            }
        }

        // annual gross yield
        $total_percent = 0;
        $length  = 0;
        foreach ($properties_of_monthly_income as $prop) {
            $total_percent += $prop->percent;
            $length += 1;
        }

        if (count($properties_of_monthly_income) != 0) {
            $annual_gross_yield = $total_percent / count($properties_of_monthly_income);
        } else {
            $annual_gross_yield = 0;
        };

        return response()->json([
            'receipts' => $receipts->count(),
            'number_of_shares'=> count($shares_funder),
            'my_investments' => $investment,
            'deposit' => $deposit,
            'number_of_properties' => count($properties),
            'monthly_income' => $monthly_income,
            'annual_gross_yield' => $annual_gross_yield
        ]);
    }

    public function propOfSheres()
    {
        $user = auth()->user();
        $properties =  $this->getPropOfFunders('', $user);

        $receipts = $user->receipts->where('status', 'pending');
        $properties_panding = [];
        foreach ($receipts as $receipt) {
            array_push($properties_panding, $receipt->property);
        }
        $allProperty = array_unique($properties_panding);
        $arr = [...$allProperty];

        return response()->json([
            'properties' => $properties,
            'properties_panding' => $arr
        ]);
    }

    public function propertyDetails($id)
    {
        $user = auth()->user();
        $property = Property::find($id);

        $annualReturn = $property->estimated_annualised_return;
        $shereFunder = Funder::where(['user_id' => $user->id, 'property_id' => $property->id, 'status' => 'funder'])->get();
        $sherePending = Funder::where(['user_id' => $user->id, 'property_id' => $property->id, 'status' => 'panding'])->get();
        $theOwnerShip = count($shereFunder) / $property->funder_count  * 100;
        $investedAmount = count($shereFunder) * $property->property_price / $property->funder_count;

        // total rentel income
        $date = Carbon::now();
        $total_rent_income = 0;
        $all_rents = Rent::where(['property_id' => $id])->get();
        foreach ($shereFunder as $shere) {
            foreach ($all_rents as $rent) {
                # code...
                if ($shere->updated_at->between($rent->start_date, $rent->end_date)) {
                    $monthsDifference = $shere->updated_at->diffInMonths($rent->end_date);
                    $total_rent_income += $rent->monthly_income / $property->funder_count * $monthsDifference;
                } elseif ($shere->lessThan($rent->start_date) && !$shere->updated_at->between($rent->start_date, $rent->end_date)) {
                    $monthsDifference = $rent->start_date->diffInMonths($rent->end_date);
                    $total_rent_income += $rent->monthly_income / $property->funder_count * $monthsDifference;
                } elseif ($rent->end_date->isPast()) {
                    $monthsDifference = $rent->start_date->diffInMonths($date);
                    $total_rent_income += $rent->monthly_income / $property->funder_count * $monthsDifference;
                }
            }
        }

        // // the last payment
        $the_last_payment = 0;
        $rent_active = Rent::where(['property_id' => $property->id, 'status' => 'active'])->first();
        $last_rent_not_active = Rent::where(['property_id' => $property->id, 'status' => 'not active'])->first();

        if ($rent_active) {
            $day = $rent_active->start_date->day;
            $today = Carbon::today();
            $oneMonthAgo = $today->day($day);
        } elseif ($last_rent_not_active) {

            if ($last_rent_not_active->start_date->day < $last_rent_not_active->end_date->day) {

                $oneMonthAgo = $last_rent_not_active->end_date->day($last_rent_not_active->start_date->day);
            } elseif ($last_rent_not_active->start_date->day > $last_rent_not_active->end_date->day) {

                $dat = $last_rent_not_active->end_date->day($last_rent_not_active->start_date->day);
                $oneMonthAgo = $dat->subMonth();
            } else {
                $oneMonthAgo = $last_rent_not_active->end_date->day($last_rent_not_active->start_date->day);
            }
        }



        $shere_count_last_month = Funder::where(['property_id' => $id, 'user_id' => $user->id])->whereDate('updated_at', '<', $oneMonthAgo)->get();

        foreach ($all_rents as $rent) {
            if ($oneMonthAgo->between($rent->start_date, $rent->end_date)) {
                $the_last_payment += $rent->$rent->monthly_income / $property->funder_count * count($shere_count_last_month);
            }
        }

        $current_rent = 0;

        if ($rent_active) {
            $current_rent = $last_rent_not_active->monthly_income - $rent_active->monthly_income;
        }

        $expected_next_payment = '';

        if ($rent_active) {
            $day = $rent_active->start_date->day;
            $today = Carbon::today();
            $newDate = $today->day($day);
            $dateAfterMonth = $newDate->addMonth();
            if ($dateAfterMonth->lessThan($rent_active->end_date)) {
                $expected_next_payment = $dateAfterMonth;
            } else {
                $expected_next_payment = 'The property is not rented';
            }
        } else {
            $expected_next_payment = 'The property is not rented';
        }


        return response()->json([
            'property' => $property,
            'property_price' => $property->property_price,
            'annualised_return' => $annualReturn,
            'current_evaluation' => $property->current_evaluation,
            'current_rent' => $current_rent,
            'invested_amount' => $investedAmount,
            'shere_funder' => $shereFunder,
            'shere_pending' => $sherePending,
            'my_owner_ship' => $theOwnerShip . '%',
            'total_rent_received' => $total_rent_income,
            'the_last_payment' => $the_last_payment,
            'expected_next_payment' => $expected_next_payment,
        ]);
    }

    private function getPropOfFunders($status, $user)
    {
        if ($status ==  '') {
            $funders = $user->funders;
        } else {
            $funders = $user->funders->where('status', $status);
        }

        $properties = [];
        foreach ($funders as $funder) {
            $property = $funder->property;
            array_push($properties, $property);
        }
        $allProperty = array_unique($properties);
        $arr = [...$allProperty];

        return $arr;
    }
}
