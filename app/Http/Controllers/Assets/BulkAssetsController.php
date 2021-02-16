<?php

namespace App\Http\Controllers\Assets;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetCheckoutRequest;
use App\Http\Traits\AssetCheckoutTrait;
use App\Models\Asset;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkAssetsController extends Controller
{
    use AssetCheckoutTrait;
    /**
     * Display the bulk edit page.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @return View
     * @internal param int $assetId
     * @since [v2.0]
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Request $request)
    {
        $this->authorize('update', Asset::class);

        if (!$request->filled('ids')) {
            return redirect()->back()->with('error', 'No assets selected');
        }

        $asset_ids = array_keys($request->input('ids'));

        if ($request->filled('bulk_actions')) {
            switch ($request->input('bulk_actions')) {
                case 'labels':
                    return view('hardware/labels')
                        ->with('assets', Asset::find($asset_ids))
                        ->with('settings', Setting::getSettings())
                        ->with('bulkedit', true)
                        ->with('count', 0);
                case 'delete':
                    $assets = Asset::with('assignedTo', 'location')->find($asset_ids);
                    $assets->each(function ($asset) {
                        $this->authorize('delete', $asset);
                    });
                    return view('hardware/bulk-delete')->with('assets', $assets);
                case 'edit':
                    return view('hardware/bulk')
                        ->with('assets', request('ids'))
                        ->with('statuslabel_list', Helper::statusLabelList());
            }
        }
        return redirect()->back()->with('error', 'No action selected');
    }

    /**
     * Save bulk edits
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @return Redirect
     * @internal param array $assets
     * @since [v2.0]
     */
    public function update(Request $request)
    {
        $this->authorize('update', Asset::class);

        \Log::debug($request->input('ids'));

        if (!$request->filled('ids') || count($request->input('ids')) <= 0) {
            return redirect()->route("hardware.index")->with('warning', trans('No assets selected, so nothing was updated.'));
        }

        $assets = array_keys($request->input('ids'));

        if (($request->filled('purchase_date'))
            || ($request->filled('expected_checkin'))
            || ($request->filled('purchase_cost'))
            || ($request->filled('supplier_id'))
            || ($request->filled('order_number'))
            || ($request->filled('warranty_months'))
            || ($request->filled('rtd_location_id'))
            || ($request->filled('requestable'))
            || ($request->filled('company_id'))
            || ($request->filled('current_company_id'))
            || ($request->filled('status_id'))
            || ($request->filled('model_id'))
            || ($request->filled('focal_point_id'))
        ) {

            $this->update_array = [];

            $this->conditionallyAddItem('purchase_date')
                ->conditionallyAddItem('expected_checkin')
                ->conditionallyAddItem('model_id')
                ->conditionallyAddItem('order_number')
                ->conditionallyAddItem('requestable')
                ->conditionallyAddItem('status_id')
                ->conditionallyAddItem('supplier_id')
                ->conditionallyAddItem('warranty_months')
                ->conditionallyAddItem('focal_point_id');

            if ($request->filled('purchase_cost')) {
                $this->update_array['purchase_cost'] =  Helper::ParseFloat($request->input('purchase_cost'));
            }

            if ($request->filled('company_id')) {
                $this->update_array['company_id'] =  $request->input('company_id');
                if ($request->input('company_id') == "clear") {
                    $this->update_array['company_id'] = null;
                }
            }

            if ($request->filled('current_company_id')) {
                $this->update_array['current_company_id'] =  $request->input('current_company_id');
                if ($request->input('current_company_id') == "clear") {
                    $this->update_array['current_company_id'] = null;
                }
            }

            if ($request->filled('rtd_location_id')) {
                $this->update_array['rtd_location_id'] = $request->input('rtd_location_id');
                if (($request->filled('update_real_loc')) && (($request->input('update_real_loc')) == '1')) {
                    $this->update_array['location_id'] = $request->input('rtd_location_id');
                }
            }

            foreach ($assets as $assetId) {
                DB::table('assets')
                    ->where('id', $assetId)
                    ->update($this->update_array);
            } // endforeach
            return redirect()->route("hardware.index")->with('success', trans('admin/hardware/message.update.success'));
            // no values given, nothing to update
        }
        return redirect()->route("hardware.index")->with('warning', trans('admin/hardware/message.update.nothing_updated'));
    }

    /**
     * Array to store update data per item
     * @var Array
     */
    private $update_array;

    /**
     * Adds parameter to update array for an item if it exists in request
     * @param  String $field field name
     * @return BulkAssetsController Model for Chaining
     */
    protected function conditionallyAddItem($field)
    {
        if (request()->filled($field)) {
            $this->update_array[$field] = request()->input($field);
        }
        return $this;
    }

    /**
     * Save bulk deleted.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param Request $request
     * @return View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @internal param array $assets
     * @since [v2.0]
     */
    public function destroy(Request $request)
    {
        $this->authorize('delete', Asset::class);

        if ($request->filled('ids')) {
            $assets = Asset::find($request->get('ids'));
            foreach ($assets as $asset) {
                $update_array['deleted_at'] = date('Y-m-d H:i:s');
                $update_array['assigned_to'] = null;

                DB::table('assets')
                    ->where('id', $asset->id)
                    ->update($update_array);
            } // endforeach
            return redirect()->to("hardware")->with('success', trans('admin/hardware/message.delete.success'));
            // no values given, nothing to update
        }
        return redirect()->to("hardware")->with('info', trans('admin/hardware/message.delete.nothing_updated'));
    }

    /**
     * Show Bulk Checkout Page
     * @return View View to checkout multiple assets
     */
    public function showCheckout()
    {
        $this->authorize('checkout', Asset::class);
        // Filter out assets that are not deployable.

        return view('hardware/bulk-checkout');
    }

    /**
     * Process Multiple Checkout Request
     * @return View
     */
    public function storeCheckout(AssetCheckoutRequest $request)
    {
        // try {
        $admin = Auth::user();

        if (!is_array($request->get('selected_assets'))) {
            return redirect()->to('hardware/bulkcheckout')->withInput()->with('error', trans('admin/hardware/message.checkout.no_assets_selected'));
        }

        $asset_ids = array_filter($request->get('selected_assets'));

        // if (request('checkout_to_type') == 'asset') {
        //     foreach ($asset_ids as $asset_id) {
        //         if ($target->id == $asset_id) {
        //             return redirect()->back()->with('error', 'You cannot check an asset out to itself.');
        //         }
        //     }
        // }
        $checkout_at = date("Y-m-d H:i:s");
        if (($request->filled('checkout_at')) && ($request->get('checkout_at') != date("Y-m-d"))) {
            $checkout_at = e($request->get('checkout_at'));
        }

        $expected_checkin = '';

        if ($request->filled('expected_checkin')) {
            $expected_checkin = e($request->get('expected_checkin'));
        }

        $errors = [];
        $target = $this->determineCheckoutTarget();

        if (isset($target)) {
            $location = $this->determineCheckoutLocation($target);

            // DB::transaction(function () use ($target, $admin, $checkout_at, $expected_checkin, $errors, $asset_ids, $request, $location) {

            foreach ($asset_ids as $asset_id) {
                $asset = Asset::findOrFail($asset_id);
                $error = $asset->checkOut($target, $admin, $checkout_at, $expected_checkin, e($request->get('notes')), $location);
                // if ($target->location_id != '') {
                //     $asset->location_id = $target->location_id;
                //     $asset->unsetEventDispatcher();
                //     $asset->save();
                // }

                if (!$error) {
                    $errors[] = $asset->getErrors();
                }
            }
            // });
            // dd($target, $location, $asset_ids, $error, $errors, !$errors, empty($errors), isset($errors));
            if (empty($errors)) {
                // Redirect to the new asset page
                return redirect()->to("hardware")->with('success', trans('admin/hardware/message.checkout.success'));
            }
            return redirect()->to("hardware/bulkcheckout")->with('error', trans('admin/hardware/message.checkout.error'))->withErrors($errors);
        }

        // Redirect to the asset management page with error
        return redirect()->to("hardware/bulkcheckout")->with('error', trans('admin/hardware/message.checkout.error'))->withErrors(['message' => "Checkout Target not found"]);
        // } catch (ModelNotFoundException $e) {
        //     return redirect()->to("hardware/bulkcheckout")->with('error', $e->getErrors());
        // }
    }
}
