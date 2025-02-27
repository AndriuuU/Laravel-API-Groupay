<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = $request->user()->groups()->with('creator')->get();
        
        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:trip,home,event,project,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category ?? 'other',
            'created_by' => $request->user()->id,
        ]);

        // Añadir al creador como miembro administrador
        $group->members()->attach($request->user()->id, ['role' => 'admin']);

        return response()->json($group, 201);
    }

    public function show(Request $request, $id)
    {
        $group = Group::with(['members', 'creator'])->findOrFail($id);
        
        // Verificar si el usuario es miembro del grupo
        if (!$group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($group);
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        // Verificar si el usuario es administrador del grupo
        if (!$group->members()->where('user_id', $request->user()->id)->where('role', 'admin')->exists()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:trip,home,event,project,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group->update([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category ?? $group->category,
        ]);

        return response()->json($group);
    }

    public function destroy(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        // Verificar si el usuario es el creador del grupo
        if ($group->created_by !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'Grupo eliminado correctamente']);
    }

    public function members($id)
    {
        $group = Group::findOrFail($id);
        $members = $group->members()->get(['users.id', 'users.name', 'users.email', 'group_user.role']);
        
        return response()->json($members);
    }

    public function addMember(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        // Verificar si el usuario es administrador del grupo
        if (!$group->members()->where('user_id', $request->user()->id)->where('role', 'admin')->exists()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Verificar si el usuario ya es miembro
        if ($group->members->contains($user->id)) {
            return response()->json(['message' => 'El usuario ya es miembro del grupo'], 422);
        }

        $group->members()->attach($user->id, ['role' => 'member']);

        return response()->json(['message' => 'Miembro añadido correctamente']);
    }

    public function removeMember(Request $request, $groupId, $memberId)
    {
        $group = Group::findOrFail($groupId);
        
        // Verificar si el usuario es administrador del grupo
        if (!$group->members()->where('user_id', $request->user()->id)->where('role', 'admin')->exists()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // No permitir eliminar al creador del grupo
        if ($group->created_by == $memberId) {
            return response()->json(['message' => 'No se puede eliminar al creador del grupo'], 422);
        }

        $group->members()->detach($memberId);

        return response()->json(['message' => 'Miembro eliminado correctamente']);
    }

    public function balances(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        // Verificar si el usuario es miembro del grupo
        if (!$group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Aquí implementarías la lógica para calcular los balances
        // Este es un cálculo básico, puedes mejorarlo según tus necesidades
        $balances = [];
        $members = $group->members;
        
        foreach ($members as $member) {
            // Calcular cuánto ha pagado este miembro
            $paid = $group->expenses()
                ->where('paid_by', $member->id)
                ->sum('amount');
            
            // Calcular cuánto debe este miembro
            $owes = 0;
            foreach ($group->expenses as $expense) {
                if ($expense->participants->contains($member->id)) {
                    $owes += $expense->amount / $expense->participants->count();
                }
            }
            
            $balance = $paid - $owes;
            
            $balances[] = [
                'userId' => $member->id,
                'userName' => $member->name,
                'amount' => round($balance, 2)
            ];
        }

        return response()->json($balances);
    }
}
