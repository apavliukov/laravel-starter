---
paths:
  - 'app/Livewire/Admin/**'
---

# Livewire Admin

## Ownership refusals are DomainExceptions shown as error toasts
The package throws Tenancy\OwnershipException (demote/remove the owner, hand out OWNER, delete a user who owns an organization) and Tenancy\MembershipPolicyException (second tenant under memberships=single); both extend DomainException. Admin components use App\Traits\Livewire\ShowsDomainRefusals (Livewire exception hook: DomainException → toast variant 'error', propagation stopped) and call the Action directly — no try/catch per trigger, never let it 500, never pre-check the rule in the component. Close a modal before the Action call, not after. Disable the action up front where the state is known: the users list reads owned_organizations_count (ListUsersQuery withCount) and renders a disabled delete for owners; the member list renders the owner row read-only with a transfer button behind OrganizationAbility::TRANSFER_OWNERSHIP.
