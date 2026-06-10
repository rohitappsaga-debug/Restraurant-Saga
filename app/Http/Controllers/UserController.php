<?php
namespace App\Http\Controllers;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller {
    public function __construct(protected UserService $service) {}

    public function index() { return response()->json($this->service->getAll()); }
    public function show($id) { return response()->json($this->service->find($id)); }
    public function store(Request $request) { return response()->json($this->service->create($request->all()), 201); }
    public function update(Request $request, $id) { return response()->json($this->service->update($id, $request->all())); }
    public function destroy($id) { $this->service->delete($id); return response()->json(null, 204); }
}