import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Construction Warehouse Theme Colors
                warehouse: {
                    orange: '#FF6B35',      // Safety/Construction Orange
                    steel: '#495057',        // Steel Gray
                    concrete: '#ADB5BD',     // Concrete Gray
                    yellow: '#FFC107',       // Industrial Yellow
                    darksteel: '#212529',    // Dark Steel
                    rust: '#C1440E',         // Rust/Burnt Orange
                    safety: '#FFD60A',       // Safety Yellow
                    metal: '#6C757D',        // Metallic Gray
                    beam: '#343A40',         // Steel Beam Dark
                },
                customOrange: 'oklch(75% 0.183 55.934)',
                customBorder: 'oklch(55.5% 0.163 48.998)',
                customHover: 'oklch(83.7% 0.128 66.29)',
                // Dashboard surface colors
                surface: {
                    50:  '#fafafa',
                    100: '#f4f4f5',
                    200: '#e4e4e7',
                    800: '#27272a',
                    850: '#1e1e21',
                    900: '#18181b',
                    950: '#09090b',
                },
            },
            animation: {
                'gradient-shift': 'gradientShift 15s ease infinite',
                'float': 'float 6s ease-in-out infinite',
                'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
                'slide-up': 'slideUp 0.5s ease-out',
                'ripple': 'ripple 0.6s ease-out',
                'fade-in': 'fadeIn 0.3s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
            },
            keyframes: {
                gradientShift: {
                    '0%, 100%': {
                        backgroundPosition: '0% 50%',
                    },
                    '50%': {
                        backgroundPosition: '100% 50%',
                    },
                },
                float: {
                    '0%, 100%': {
                        transform: 'translateY(0px)',
                    },
                    '50%': {
                        transform: 'translateY(-20px)',
                    },
                },
                pulseGlow: {
                    '0%, 100%': {
                        boxShadow: '0 0 5px rgba(255, 107, 53, 0.5)',
                    },
                    '50%': {
                        boxShadow: '0 0 20px rgba(255, 107, 53, 0.8), 0 0 30px rgba(255, 107, 53, 0.4)',
                    },
                },
                slideUp: {
                    from: {
                        opacity: '0',
                        transform: 'translateY(20px)',
                    },
                    to: {
                        opacity: '1',
                        transform: 'translateY(0)',
                    },
                },
                ripple: {
                    '0%': {
                        transform: 'scale(0)',
                        opacity: '1',
                    },
                    '100%': {
                        transform: 'scale(4)',
                        opacity: '0',
                    },
                },
                fadeIn: {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                slideInRight: {
                    from: { opacity: '0', transform: 'translateX(20px)' },
                    to: { opacity: '1', transform: 'translateX(0)' },
                },
                scaleIn: {
                    from: { opacity: '0', transform: 'scale(0.95)' },
                    to: { opacity: '1', transform: 'scale(1)' },
                },
            },
            backdropBlur: {
                xs: '2px',
            },
        },
    },

    plugins: [forms],
};
