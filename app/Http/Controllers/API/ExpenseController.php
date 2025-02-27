<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        
        // Verificar si el usuario es miembro del grupo
        if (!$group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $expenses = $group->expenses()
            ->with(['paidBy', 'participants'])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($expenses);
    }

    public function store(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        
        // Verificar si el usuario es miembro del grupo
        if (!$group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'nullable|string',
            'paidBy' => 'required|exists:users,id',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que el pagador y los participantes son miembros del grupo
        $memberIds = $group->members->pluck('id')->toArray();
        if (!in_array($request->paidBy, $memberIds)) {
            return response()->json(['message' => 'El pagador no es miembro del grupo'], 422);
        }

        foreach ($request->participants as $participantId) {
            if (!in_array($participantId, $memberIds)) {
                return response()->json(['message' => 'Uno o más participantes no son miembros del grupo'], 422);
            }
        }

        $expense = Expense::create([
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $request->date,
            'category' => $request->category ?? 'other',
            'group_id' => $groupId,
            'paid_by' => $request->paidBy,
        ]);

        $expense->participants()->attach($request->participants);

        return response()->json($expense->load(['paidBy', 'participants']), 201);
    }

    public function show(Request $request, $id)
    {
        $expense = Expense::with(['paidBy', 'participants', 'group'])->findOrFail($id);
        
        // Verificar si el usuario es miembro del grupo
        if (!$expense->group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        
        // Verificar si el usuario es miembro del grupo
        if (!$expense->group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'nullable|string',
            'paidBy' => 'required|exists:users,id',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que el pagador y los participantes son miembros del grupo
        $memberIds = $expense->group->members->pluck('id')->toArray();
        if (!in_array($request->paidBy, $memberIds)) {
            return response()->json(['message' => 'El pagador no es miembro del grupo'], 422);
        }

        foreach ($request->participants as $participantId) {
            if (!in_array($participantId, $memberIds)) {
                return response()->json(['message' => 'Uno o más participantes no son miembros del grupo'], 422);
            }
        }

        $expense->update([
            'description' => $request->description,
            'amount' => $request->amount,
            'date' => $request->date,
            'category' => $request->category ?? $expense->category,
            'paid_by' => $request->paidBy,
        ]);

        // Actualizar participantes
        $expense->participants()->sync($request->participants);

        return response()->json($expense->load(['paidBy', 'participants']));
    }

    public function destroy(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        
        // Verificar si el usuario es miembro del grupo
        if (!$expense->group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $expense->delete();

        return response()->json(['message' => 'Gasto eliminado correctamente']);
    }
}
