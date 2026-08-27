import { ref } from 'vue';

export function useAuth() {
  const permissions = ref(new Set([
    'payment_plans.pay',
    'inventory.move',
    'vendors.po',
    'vendors.create',
    'vendors.view',
  ]));

  function can(permission) {
    return permissions.value.has(permission);
  }

  function setPermissions(perms) {
    permissions.value = new Set(perms);
  }

  return { can, setPermissions };
}