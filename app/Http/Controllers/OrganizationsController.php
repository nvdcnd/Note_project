<?php

namespace App\Http\Controllers;

use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Organization2userTransaction;
use App\Models\OrganizationsMember;
use App\Models\PivotForNote;
use App\Models\Replynote;
use App\Models\Theme4orgWallet;
use App\Models\User2organizationTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrganizationsController extends Controller
{
    public function index()
    {
        $hostedOrganizations = Organization::query()->where('hostID', Auth::id())->get();

        $memberOrganizations = Organization::query()
            ->whereIn('id', OrganizationsMember::query()
                ->where('userID', Auth::id())
                ->where('status', true)
                ->select('organizationID'))
            ->get();

        $pendingOrganizations = OrganizationsMember::query()
            ->where('userID', Auth::id())
            ->where('status', false)
            ->with('organization')
            ->get();

        return view('organizations.index', [
            'hostedOrganizations' => $hostedOrganizations,
            'memberOrganizations' => $memberOrganizations->whereNotIn('id', $hostedOrganizations->pluck('id')),
            'pendingOrganizations' => $pendingOrganizations,
        ]);
    }

    public function show(Request $request, $id)
    {
        $organization = Organization::query()->find($id);
        if (! $organization) {
            abort(404);
        }

        $isHost = $organization->hostID === Auth::id();
        $isMember = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('userID', Auth::id())
            ->where('status', true)
            ->exists();

        if (! $isHost && ! $isMember) {
            abort(403, 'You are not a member of this organization');
        }

        $notes = Note::query()
            ->where('organizationID', $organization->id)
            ->latest()
            ->take(20)
            ->get();

        $doneNoteIds = MarkAsDone::query()
            ->where('userID', Auth::id())
            ->where('status', true)
            ->pluck('noteID')
            ->all();

        $members = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('status', true)
            ->with('user')
            ->get();

        return view('organizations.show', [
            'organization' => $organization,
            'isHost' => $isHost,
            'notes' => $notes,
            'doneNoteIds' => $doneNoteIds,
            'members' => $members,
        ]);
    }

    public function dashboard(Request $request, $id)
    {
        $organization = Organization::query()->find($id);
        if (! $organization) {
            abort(404);
        }

        if ($organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)->with('error', 'You are not authorized to view this dashboard');
        }

        $currentMembers = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('status', true)
            ->count();

        $pendingMembers = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('status', false)
            ->count();

        $allNotes = Note::query()->where('organizationID', $organization->id)->count();
        $undoneNotes = Note::query()->where('organizationID', $organization->id)->where('org_done', false)->count();
        $doneNotes = Note::query()->where('organizationID', $organization->id)->where('org_done', true)->count();

        $currentMemberList = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('status', true)
            ->with('user')
            ->get();

        $pendingMemberList = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('status', false)
            ->with('user')
            ->get();

        return view('organizations.dashboard', [
            'organization' => $organization,
            'currentMembers' => $currentMembers,
            'pendingMembers' => $pendingMembers,
            'allNotes' => $allNotes,
            'undoneNotes' => $undoneNotes,
            'doneNotes' => $doneNotes,
            'currentMemberList' => $currentMemberList,
            'pendingMemberList' => $pendingMemberList,
        ]);
    }

    public function members(Request $request, $id)
    {
        $organization = Organization::query()->find($id);
        if (! $organization) {
            abort(404);
        }

        if ($organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)->with('error', 'You are not authorized to view members');
        }

        $currentMemberList = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('status', true)
            ->with('user')
            ->get();

        $pendingMemberList = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('status', false)
            ->with('user')
            ->get();

        return view('organizations.members', [
            'organization' => $organization,
            'currentMemberList' => $currentMemberList,
            'pendingMemberList' => $pendingMemberList,
        ]);
    }

    public function settings(Request $request, $id)
    {
        $organization = Organization::query()->find($id);
        if (! $organization) {
            abort(404);
        }

        if ($organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)->with('error', 'You are not authorized to manage this organization');
        }

        $pendingHostRequests = \App\Models\PivotChangeHostOrganization::query()
            ->where('organizationID', $organization->id)
            ->where('new_host_acceptance_status', false)
            ->with('newHost')
            ->get();

        return view('organizations.settings', [
            'organization' => $organization,
            'pendingHostRequests' => $pendingHostRequests,
        ]);
    }

    public function balance(Request $request, $id)
    {
        $organization = Organization::query()->find($id);
        if (! $organization) {
            abort(404);
        }

        $isHost = $organization->hostID === Auth::id();
        $isMember = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('userID', Auth::id())
            ->where('status', true)
            ->exists();

        if (! $isHost && ! $isMember) {
            abort(403);
        }

        $user2org = User2organizationTransaction::query()->where('organizationID', $organization->id)->latest()->take(50)->get();
        $org2user = Organization2userTransaction::query()->where('organizationID', $organization->id)->latest()->take(50)->get();

        return view('organizations.balance', compact('organization', 'isHost', 'user2org', 'org2user'));
    }

    public function create()
    {
        return view('organizations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($validated) {
            $organization = Organization::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'hostID' => Auth::id(),
            ]);

            OrganizationsMember::create([
                'organizationID' => $organization->id,
                'userID' => Auth::id(),
                'status' => true,
            ]);

            return redirect()->route('organization', $organization->id)->with('success', 'Organization created successfully');
        });
    }

    public function edit_organization(Request $request, $id)
    {
        $organization = Organization::find($id);
        if (! $organization || $organization->hostID != Auth::user()->id) {
            return redirect()->route('home')->with('error', 'You are not authorized to edit this organization');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $organization->update($validated);

        return redirect()->route('organization', $id)->with('success', 'Organization edited successfully');
    }

    public function delete_organization(Request $request, $id)
    {
        $organization = Organization::find($id);
        if (! $organization || $organization->hostID != Auth::user()->id) {
            return redirect()->route('home')->with('error', 'You are not authorized to delete this organization');
        }

        DB::transaction(function () use ($organization) {
            $noteIds = Note::query()->where('organizationID', $organization->id)->pluck('id');

            if ($noteIds->isNotEmpty()) {
                PivotForNote::query()->whereIn('note_id', $noteIds)->delete();
                MarkAsDone::query()->whereIn('noteID', $noteIds)->delete();
                Replynote::query()->whereIn('noteID', $noteIds)->delete();
            }

            Note::query()->where('organizationID', $organization->id)->delete();
            OrganizationsMember::query()->where('organizationID', $organization->id)->delete();
            Theme4orgWallet::query()->where('organizationID', $organization->id)->delete();
            \App\Models\PivotChangeHostOrganization::query()->where('organizationID', $organization->id)->delete();
            $organization->delete();
        });

        return redirect()->route('organizations.index')->with('success', 'Organization deleted successfully');
    }
}
