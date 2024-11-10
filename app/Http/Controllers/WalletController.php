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
        $receipt_accepted = $receipts->where('status', 'accepted');

        // my investment
        $investment = 0;
        foreach ($receipt_accepted as $receipt) {
            $sheres_count = $receipt->count_sheres;
            $property = Property::find($receipt->property_id);
            $receipt_price = $property->property_price * $sheres_count;
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
                    $monthly_income += (intval($prop->current_rent) * $count_shere);
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
        $theOwnerShip = $property->funder_count / count($shereFunder) * 100 / 100;
        $investedAmount = count($shereFunder) * $property->property_price;
        $investmentValue = $property->current_evaluation * count($shereFunder) / 100;

        $date = Carbon::now();
        $purchase_date = Funder::where(['user_id' => $user->id, 'property_id' => $property->id])->orderBy('created_at', 'asc')->first();

        $allrents = Rent::where('property_id', $property->id)->where('start_date', '<', $date)->where('start_date', '>', $purchase_date->created_at)->get();
        $total_rent_received = 0;
        foreach ($allrents as $rent) {
            $total_rent_received += count($shereFunder) * $rent->monthly_income;
        }

        $the_last_payment = 0;
        $rentActive = Rent::where(['property_id' => $property->id, 'status' => 'active'])->first();
        $lastRent = Rent::where(['property_id' => $property->id])->orderBy('created_at', 'desc')->first();
        if ($rentActive) {
            $the_last_payment = $rentActive->monthly_income * count($shereFunder);
        } elseif ($lastRent) {
            $the_last_payment = $lastRent->monthly_income * count($shereFunder);
        }

        $current_rent = $property->current_rent * count($shereFunder);

        $expected_next_payment = 'The property is not rented';
        if ($rentActive) {
            $start_date = Carbon::parse($rentActive->start_date);
            $day = $start_date->day;
            $expected_next_payment  = $date->setDay($day)->formatLocalized('%d %B %Y');
        }

        return response()->json([
            'property' => $property,
            'property_price' => $property->property_price,
            'annualised_return' => $annualReturn,
            'current_evaluation' => $property->current_evaluation,
            'current_rent' => $current_rent,
            'invested_amount' => $investedAmount,
            'investment_value' => $investmentValue,
            'my_owner_ship' => $theOwnerShip,
            'total_rent_received' => $total_rent_received,
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
