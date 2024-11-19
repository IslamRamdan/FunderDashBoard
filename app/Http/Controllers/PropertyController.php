<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Funder;
use App\Models\Location;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::all();
        return view('Properties.properties', ['properties' => $properties]);
    }

    public function show()
    {
        $categories = Category::all();
        return view('Properties.create', ['categories' => $categories]);
    }

    public function soldout()
    {
        $properties = Property::where(['status' => 'sold out', 'approved' => null])->get();;
        return view('Properties.properties', ['properties' => $properties]);
    }

    public function available()
    {
        $properties = Property::where('status', 'available')->get();
        return view('Properties.properties', ['properties' => $properties]);
    }

    public function readMore($id)
    {
        $property = Property::find($id);
        return view('Properties.readmore', ['property' => $property]);
    }

    // all properties
    public function all()
    {
        $properties = Property::all();
        $user = auth()->user();
        foreach ($properties as $property) {
            $favorite = Favorite::where(['property_id' => $property->id, 'user_id' => $user->id])->first();
            if ($favorite) {
                $property->if_favorite = 'true';
            } elseif (!$favorite) {
                $property->if_favorite = 'false';
            }
            $share_price = $property->property_price_total / $property->funder_count; 
        }

        return response()->json([
            'success' => true,
            'properties' => $properties
        ]);
    }
    // properties soldout
    public function propertiesSoldout()
    {
        $properties = Property::where('status', 'sold out')->get();

        return response()->json([
            'success' => true,
            'properties' => $properties
        ]);
    }

    // get property by id
    public function propById($id)
    {
        $property = Property::find($id);
        if (!$property) {
            return response()->json([
                'error' => 'property not found',
            ], 400);
        }


        $property->funders = $property->funders;
        $property->timelines = $property->timelines;
        $property->location = $property->location;
        $user = auth()->user();

        $user_shared = Funder::where(['property_id' => $property->id, 'user_id' => $user->id])->get();
        if (count($user_shared) > 0) {
            $property->if_user_shared = true;
        } else {
            $property->if_user_shared = false;
        }

        return response()->json([
            'success' => true,
            'properties' => $property,
            'user-count-shars' => $user_shared
        ]);
    }

    // properties by category name
    public function propByCateName($name)
    {
        $category = Category::where('name', $name)->first();
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'properties' => $category->properties
        ]);
    }

    // create property
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'images' => 'required||max:5',
            'description' => 'required',
            'funded_date' => 'required|date',
            'purchase_price' => 'required',
            'funder_count' => 'required',
            'rental_income' => 'required',
            'current_rent' => 'required',
            'percent' => 'required',
            'location_string' => 'required',
            'property_price_total' => 'required',
            'service_charge' => 'required',
            'current_evaluation' => 'required',
            'discount' => 'required',
            'estimated_annualised_return' => 'required',
            'estimated_annual_appreciation' => 'required',
            'estimated_projected_gross_yield' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'category_id' => 'required',
        ]);

        $property = new Property();

        $property->name = $request->name;
        $property->description = $request->description;
        $property->current_evaluation = $request->current_evaluation;
        $property->funded_date = $request->funded_date;
        $property->purchase_price = $request->purchase_price;
        $property->funder_count = $request->funder_count;
        $property->rental_income = $request->rental_income;
        $property->current_rent = $request->current_rent;
        $property->percent = $request->percent;
        $property->location_string = $request->location_string;
        $property->property_price_total = $request->property_price_total - intval($request->discount) / 100;
        $property->discount = $request->discount;
        $property->status = 'available';
        $property->estimated_annualised_return = $request->estimated_annualised_return;
        $property->estimated_annual_appreciation = $request->estimated_annual_appreciation;
        $property->estimated_projected_gross_yield = $request->estimated_projected_gross_yield;
        $property->service_charge = $request->service_charge;
        $property->category_id = $request->category_id;
        $property->property_price = intval($request->property_price_total / $request->funder_count);

        $imagesName = [];
        if ($request->has('images')) {
            foreach ($request->file('images') as $file) {
                $filename = Str::random(32) . "." . $file->getClientOriginalExtension();
                $file->move('storage/', $filename);
                array_push($imagesName, $filename);
            }

            $property->images = $imagesName;
            $property->save();
        }

        $location = Location::create([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'property_id' => $property->id
        ]);
        $property->location()->save($location);

        return redirect()->route('property.readMore', $property->id);
    }


    public function edit($id)
    {
        $property = Property::find($id);
        $categories = Category::all();
        return view('Properties.edit-property', ['property' => $property, 'categories' => $categories]);
    }
    // update property
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'images' => 'nullable||max:5',
            'description' => 'required',
            'funded_date' => 'required|date',
            'purchase_price' => 'required',
            'funder_count' => 'required',
            'rental_income' => 'required',
            'current_rent' => 'required',
            'percent' => 'required',
            'location_string' => 'required',
            'property_price_total' => 'required',
            'service_charge' => 'required',
            'current_evaluation' => 'required',
            'discount' => 'required',
            'estimated_annualised_return' => 'required',
            'estimated_annual_appreciation' => 'required',
            'estimated_projected_gross_yield' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'category_id' => 'required',
        ]);

        $property = Property::find($id);
        if (!$property) {
            return response()->json([
                'message' => 'property not found',
            ], 400);
        }

        $property->name = $request->name;
        $property->current_evaluation = $request->current_evaluation;
        $property->description = $request->description;
        $property->funded_date = $request->funded_date;
        $property->purchase_price = $request->purchase_price;
        $property->funder_count = $request->funder_count;
        $property->rental_income = $request->rental_income;
        $property->current_rent = $request->current_rent;
        $property->percent = $request->percent;
        $property->location_string = $request->location_string;
        $property->property_price_total = $request->property_price_total - intval($request->discount) / 100;
        $property->discount = $request->discount;
        $property->estimated_annualised_return = $request->estimated_annualised_return;
        $property->estimated_annual_appreciation = $request->estimated_annual_appreciation;
        $property->estimated_projected_gross_yield = $request->estimated_projected_gross_yield;
        $property->service_charge = $request->service_charge;
        $property->category_id = $request->category_id;
        $property->property_price = intval($request->property_price_total / $request->funder_count);

        $property->location()->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);

        $imagesA = $property->images;
        if ($request->has('images')) {
            foreach ($request->file('images') as $file) {
                $filename = Str::random(32) . "." . $file->getClientOriginalExtension();
                $file->move('storage/', $filename);
                array_push($imagesA, $filename);
            }
        }

        $property->images = [...$imagesA];

        $property->save();
        return redirect()->route('property.readMore', $id);
    }

    // delete property
    public function delete($id)
    {
        $property = Property::find($id);
        if (!$property) {
            return response()->json([
                'message' => 'property not found',
            ], 400);
        }

        $property->delete();
        return redirect()->route('property.index');
    }

    // filter properties
    public function filter(Request $request)
    {
        $request->validate([
            'search' => 'required',
        ]);

        $query = Property::query();

        $query->where('status', 'available');
        // $query->where('name', $request->search);
        $query->where('name', 'like', '%' . $request->search . '%');

        if (isset($request->location) && $request->location != null) {
            $query->where('location_string', $request->location);
        }
        if (isset($request->category) && $request->category != null) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->whereIn('id', $request->category);
            });
        }

        $properties = $query->get();

        return response()->json([
            'success' => true,
            'properties' => $properties
        ]);
    }

    // get all locations
    public function locations()
    {
        $locations = Property::distinct()->pluck('location_string');
        return response()->json([
            'success' => true,
            'locations' => $locations
        ]);
    }

    public function gosoldout($id)
    {
        $property = Property::find($id);
        $property->status = "sold out";
        $property->save();

        return redirect()->route('property.readMore', $id);
    }

    public function shares($id)
    {
        $property = Property::find($id);
        $shares = $property->funders;

        return view('Properties.shares', ['shares' => $shares, 'property' => $property]);
    }

    public function sharedelete($id)
    {
        $share = Funder::find($id);
        $share->delete();

        $property = $share->Property;
        $funders = Funder::where(['property_id' => $property->id, 'status' => 'funder'])->get();
        $pending = Funder::where(['property_id' => $property->id, 'status' => 'pending'])->orderBy('created_at', 'asc')->get();

        if (count($funders) < $property->funder_count) {
            $pending[0]->status = 'funder';
            $pending[0]->save();
            $property->status = null;
            $property->save();
        }
        return redirect()->route('property.shares', $share->property->id);
    }

    public function property_shared_user($id)
    {
        $user = User::find($id);
        $funders = $user->funders;

        $properties = [];
        foreach ($funders as $funder) {
            $property = $funder->property;
            array_push($properties, $property);
        }
        $allProperty = array_unique($properties);
        $arr = [...$allProperty];

        return view('Users.property-shered-user', ['properties' => $arr, 'user' => $user]);
    }

    public function deleteImage($property_id, $imageName)
    {

        $property = Property::find($property_id);
        $imagesA = $property->images;

        if (in_array($imageName, $imagesA)) {
            $index = array_search($imageName, $imagesA);
            if ($index !== false) {
                unset($imagesA[$index]);
                $imagePath = public_path('storage/' . $imageName);

                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                }
                $property->images = [...$imagesA];
                $property->save();
            }
        }

        return redirect()->back();
    }
}
