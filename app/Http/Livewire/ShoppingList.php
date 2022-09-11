<?php

namespace App\Http\Livewire;

use App\Models\Requirement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ShoppingList extends Component
{
    public Collection $list;

    protected $listeners = ["checkboxClicked"];

    public function render()
    {
        return view("livewire.shopping-list");
    }

    public function mount(Request $request): void
    {
        $sessionRequirements = $request->session()->get("requirements", []);
        $this->populateList($sessionRequirements);
    }

    /**
     * The action to perform in this ShoppingList component
     * whenever a ResourceCheckbox component is clicked.
     *
     * @param bool $add Whether to add or subtract from the shopping list
     * @param array $requirementIds The IDs of the Requirements for getting the Resources and quantities needed
     */
    public function checkboxClicked(
        Request $request,
        bool $add,
        array $requirementIds,
    ): void {
        $sessionRequirements = $request->session()->get("requirements", []);
//        if (Auth::check() && empty($sessionRequirements)) {
//            $sessionRequirements = Auth::user()->resources->pivot->pluck("id");
//        }

        if ($add) {
            // If we're adding, add Requirement ID to session array if it doesn't exist.
            $sessionRequirements = array_unique(
                array_merge($sessionRequirements, $requirementIds),
            );
            sort($sessionRequirements);
        } else {
            // If we're subtracting, remove from session array if it exists.
            $sessionRequirements = array_diff(
                $sessionRequirements,
                $requirementIds,
            );
        }
        $request->session()->put("requirements", $sessionRequirements);

        $this->populateList($sessionRequirements);
    }

    private function populateList($sessionRequirements): void
    {
        // Load all Requirements with given $requirementIds and aggregate their sums by Resource ID
        $requirements = Requirement::whereKey($sessionRequirements)
            ->selectRaw("resource_id, SUM(quantity_needed) AS quantity")
            ->groupBy("resource_id")
            ->get();

        // Then assign them to the $list.
        $this->list = collect();
        foreach ($requirements as $requirement) {
            $this->list->push([
                "resourceId" => $requirement->resource_id,
                "quantity" => $requirement->quantity,
            ]);
        }
    }
}
