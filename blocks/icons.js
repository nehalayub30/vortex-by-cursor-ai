/**
 * VORTEX AI Marketplace Block Icons
 *
 * SVG icons for marketplace blocks
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/blocks
 */

const { createElement } = wp.element;

export const vortexIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M12 2L2 12h3v8h14v-8h3L12 2zm0 2.8L17.2 10H16v8h-8v-8H6.8L12 4.8z',
        fill: 'currentColor'
    })
);

export const artworkIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H6c-.55 0-1-.45-1-1V6c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1zm-4.44-6.19l-2.35 3.02-1.56-1.88c-.2-.25-.58-.24-.78.01l-1.74 2.23c-.26.33-.02.81.39.81h8.98c.41 0 .65-.47.4-.8l-2.55-3.39c-.19-.26-.59-.26-.79 0z',
        fill: 'currentColor'
    })
);

export const artistIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
        fill: 'currentColor'
    })
);

export const searchIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z',
        fill: 'currentColor'
    })
);

export const cartIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z',
        fill: 'currentColor'
    })
);

export const checkoutIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z',
        fill: 'currentColor'
    })
);

export const dashboardIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z',
        fill: 'currentColor'
    })
);

export const aiGeneratorIcon = createElement('svg', 
    { 
        width: 24, 
        height: 24, 
        viewBox: '0 0 24 24' 
    },
    createElement('path', { 
        d: 'M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7z',
        fill: 'currentColor'
    }),
    createElement('path', { 
        d: 'M12 17.5c2.33 0 4.3-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z',
        fill: 'currentColor'
    }),
    createElement('path', { 
        d: 'M8.5 11A1.5 1.5 0 0 0 10 9.5 1.5 1.5 0 0 0 8.5 8 1.5 1.5 0 0 0 7 9.5 1.5 1.5 0 0 0 8.5 11zM15.5 11A1.5 1.5 0 0 0 17 9.5 1.5 1.5 0 0 0 15.5 8 1.5 1.5 0 0 0 14 9.5a1.5 1.5 0 0 0 1.5 1.5z',
        fill: 'currentColor'
    })
); 