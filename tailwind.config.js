import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const commonTextRotationAnimation = `0.75s ease-in-out forwards`;
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        {
            pattern: /animate-rotate-[xy]-*/
        }
    ],

    theme: {
        extend: {
            animation: {
                'pulse-bg-once': 'pulse-bg-once .5s ease-in forwards',
                'rotate-x--2': `rotate-x--2 ${commonTextRotationAnimation}`,
                'rotate-x--1-5': `rotate-x--1-5 ${commonTextRotationAnimation}`,
                'rotate-x--1': `rotate-x--1 ${commonTextRotationAnimation}`,
                'rotate-x--0-5': `rotate-x--0-5 ${commonTextRotationAnimation}`,
                'rotate-x-0': `rotate-x-0 ${commonTextRotationAnimation}`,
                'rotate-x-0-5': `rotate-x-0-5 ${commonTextRotationAnimation}`,
                'rotate-x-1': `rotate-x-1 ${commonTextRotationAnimation}`,
                'rotate-x-1-5': `rotate-x-1-5 ${commonTextRotationAnimation}`,
                'rotate-x-2': `rotate-x-2 ${commonTextRotationAnimation}`,
                'rotate-y--2': `rotate-y--2 ${commonTextRotationAnimation}`,
                'rotate-y--1-5': `rotate-y--1-5 ${commonTextRotationAnimation}`,
                'rotate-y--1': `rotate-y--1 ${commonTextRotationAnimation}`,
                'rotate-y--0-5': `rotate-y--0-5 ${commonTextRotationAnimation}`,
                'rotate-y-0': `rotate-y-0 ${commonTextRotationAnimation}`,
                'rotate-y-0-5': `rotate-y-0-5 ${commonTextRotationAnimation}`,
                'rotate-y-1': `rotate-y-1 ${commonTextRotationAnimation}`,
                'rotate-y-1-5': `rotate-y-1-5 ${commonTextRotationAnimation}`,
                'rotate-y-2': `rotate-y-2 ${commonTextRotationAnimation}`,
                'rotate-text-counter': `rotate-text-counter ${commonTextRotationAnimation}`,
            },
            keyframes: {
                'pulse-bg-once': {
                    '0%': { backgroundColor: 'var(--tw-gradient-from)' },
                    '15%': { backgroundColor: 'var(--tw-gradient-to)' },
                    '40%': { backgroundColor: 'var(--tw-gradient-from)' },
                    '100%': { backgroundColor: 'var(--tw-gradient-to)' },
                },

                'rotate-x--2': {
                    '0%': {
                        'transform-origin': '300% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '300% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x--1-5': {
                    '0%': {
                        'transform-origin': '237.5% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '237.5% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x--1': {
                    '0%': {
                        'transform-origin': '175% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '175% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x--0-5': {
                    '0%': {
                        'transform-origin': '112.5% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '112.5% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x-0': {
                    '0%': {
                        'transform-origin': '50% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x-0-5': {
                    '0%': {
                        'transform-origin': '-12.5% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '-12.5% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x-1': {
                    '0%': {
                        'transform-origin': '-75% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '-75% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x-1-5': {
                    '0%': {
                        'transform-origin': '-137.5% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '-137.5% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-x-2': {
                    '0%': {
                        'transform-origin': '-200% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '-200% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y--2': {
                    '0%': {
                        'transform-origin': '50% 300%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% 300%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y--1-5': {
                    '0%': {
                        'transform-origin': '50% 237.5%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% 237.5%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y--1': {
                    '0%': {
                        'transform-origin': '50% 175%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% 175%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y--0-5': {
                    '0%': {
                        'transform-origin': '50% 112.5%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% 112.5%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y-0': {
                    '0%': {
                        'transform-origin': '50% 50%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% 50%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y-0-5': {
                    '0%': {
                        'transform-origin': '50% -12.5%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% -12.5%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y-1': {
                    '0%': {
                        'transform-origin': '50% -75%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% -75%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y-1-5': {
                    '0%': {
                        'transform-origin': '50% -137.5%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% -137.5%',
                        rotate: '0deg',
                    } 
                },
                'rotate-y-2': {
                    '0%': {
                        'transform-origin': '50% -200%',
                        rotate: '-180deg',
                    },
                    '100%': {
                        'transform-origin': '50% -200%',
                        rotate: '0deg',
                    } 
                },
                'rotate-text-counter': {
                    '0%': {
                        rotate: '180deg',
                    },
                    '100%': {
                        rotate: '0deg',
                    } 
                },
            }
        },
    },

    plugins: [forms],
};
