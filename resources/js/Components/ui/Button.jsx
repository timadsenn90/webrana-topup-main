import React from 'react';
import { FaSpinner } from 'react-icons/fa';

const Button = ({
    children,
    type = 'button',
    variant = 'primary',
    size = 'md',
    loading = false,
    disabled = false,
    fullWidth = false,
    onClick,
    className = '',
    ...props
}) => {
    const baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    const variantClasses = {
        primary: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500 disabled:bg-blue-400',
        secondary: 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500 disabled:bg-gray-400',
        success: 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500 disabled:bg-green-400',
        danger: 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500 disabled:bg-red-400',
        warning: 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500 disabled:bg-yellow-400',
        outline: 'bg-transparent border-2 border-gray-300 hover:border-gray-400 text-gray-700 dark:text-gray-300 focus:ring-gray-500',
        ghost: 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300',
    };
    
    const sizeClasses = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-6 py-3 text-lg',
    };
    
    const widthClass = fullWidth ? 'w-full' : '';
    const disabledClass = (disabled || loading) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer';
    
    return (
        <button
            type={type}
            onClick={onClick}
            disabled={disabled || loading}
            className={`
                ${baseClasses}
                ${variantClasses[variant]}
                ${sizeClasses[size]}
                ${widthClass}
                ${disabledClass}
                ${className}
            `}
            {...props}
        >
            {loading && (
                <FaSpinner className="animate-spin mr-2 h-4 w-4" />
            )}
            {children}
        </button>
    );
};

export default Button;
