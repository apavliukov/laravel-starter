---
paths:
  - 'tests/Unit/Policies/**'
---

# Unit Policies

## Policy tests extend BasePolicyTestCase
Declare getPolicyClass() and use $this->policy; the base case resolves the policy from the container in setUp(). Never instantiate a policy with new.
