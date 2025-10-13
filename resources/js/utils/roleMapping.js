/**
 * Role mapping utility - Maps Latin role names (from DB ENUM) to Cyrillic display names
 */

/**
 * Role name mappings (Latin -> Cyrillic)
 */
export const roleNames = {
    SuperAdmin: 'СуперАдмин',
    Admin: 'Админ',
    Kadrovik: 'Кадровик',
    Zaposleni: 'Запослени',
    Rukovodilac: 'Руководилац',
};

/**
 * Get Cyrillic display name for a role
 *
 * @param {string} role - Latin role name from database
 * @returns {string} - Cyrillic display name
 */
export function getRoleLabel(role) {
    return roleNames[role] || role;
}

/**
 * Role badge CSS classes
 * Used for displaying role badges in the UI
 */
export const roleBadgeClasses = {
    SuperAdmin: 'bg-red-100 text-red-800',
    Admin: 'bg-purple-100 text-purple-800',
    Kadrovik: 'bg-blue-100 text-blue-800',
    Zaposleni: 'bg-gray-100 text-gray-800',
    Rukovodilac: 'bg-indigo-100 text-indigo-800',
};

/**
 * Get badge CSS classes for a role
 *
 * @param {string} role - Latin role name from database
 * @returns {string} - CSS classes for badge styling
 */
export function getRoleBadgeClass(role) {
    return roleBadgeClasses[role] || 'bg-gray-100 text-gray-800';
}
