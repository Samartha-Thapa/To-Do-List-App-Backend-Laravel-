<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index() {
        $tasks = Task::where('user_id', Auth::id())->get();
        return response()->json($tasks);
    }

    public function show($id){
        $task = Task::with('user')->findOrFail($id);
        return response()->json($task);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'priority' => 'in:low,medium,high,urgent',
            'assignedDate' => 'required|date',
            'dueDate' => 'required|date|after_or_equal:assignedDate',
            'progress' => 'integer|min:0|max:100',
            'completed' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();

        $task = Task::create($validated);
        return response()->json($task, 201);
    }

    public function update(Request $request, $id) {
        $task = Task::findOrFail($id);
        $task->update($request->all());
        return response()->json($task, 200);
    }

    public function destroy($id) {
        Task::destroy($id);
        return response()->json(null, 204);
    }
}
