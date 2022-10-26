<?php

// class HomeController extends BaseController {
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentCategories;

use App\Models\Branch;

use App\Models\Categories;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class HomeController extends Controller
{

    public array $categoriesList = array();
    public array $branchList = array();
    public array $studentCatList = array();

    public function __construct()
    {
        $this->categoriesList = Categories::select()->orderBy('category')->get();
        $this->branchList = Branch::select()->orderBy('id')->get();
        $this->studentCatList = StudentCategories::select()->orderBy('cat_id')->get();
    }

    public function home(): View|Factory
    {
        return view('panel.index')
            ->with('categories_list', $this->categoriesList)
            ->with('branch_list', $this->branchList)
            ->with('student_categories_list', $this->studentCatList);
    }
}
