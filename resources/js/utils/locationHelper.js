/**
 * Location detection helper based on IP address
 * Office IPs:
 *  - 10.15.32.x (LAN - wired network)
 *  - 172.x.x.x (WiFi network)
 * Remote IPs: anything else (home/public IPs)
 */

export const getLocationFromIp = (ip) => {
    if (!ip || ip === 'unknown' || ip === '-') {
        return {
            label: 'Непознато',
            icon: '❓',
            color: 'gray',
            bgColor: 'bg-gray-100',
            textColor: 'text-gray-700',
            borderColor: 'border-gray-300',
        };
    }

    // Check if IP is from office network
    // LAN (wired): 10.15.32.x
    // WiFi: 172.x.x.x
    if (ip.startsWith('10.15.32.') || ip.startsWith('172.')) {
        return {
            label: 'Канцеларија',
            icon: '🏢',
            color: 'green',
            bgColor: 'bg-green-100',
            textColor: 'text-green-700',
            borderColor: 'border-green-300',
        };
    }

    // Everything else is considered remote (home/public IP)
    return {
        label: 'Удаљено',
        icon: '🏠',
        color: 'blue',
        bgColor: 'bg-blue-100',
        textColor: 'text-blue-700',
        borderColor: 'border-blue-300',
    };
};

/**
 * Check if IP is from office location
 * Office: 10.15.32.x or 172.x.x.x
 */
export const isOfficeIp = (ip) => {
    return ip && (ip.startsWith('10.15.32.') || ip.startsWith('172.'));
};

/**
 * Check if IP is remote location
 * Remote: anything that is not office IP
 */
export const isRemoteIp = (ip) => {
    return ip && !isOfficeIp(ip) && ip !== 'unknown' && ip !== '-';
};
