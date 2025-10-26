import React, { useEffect, useState } from 'react';
import { FaCheckCircle, FaExclamationCircle, FaInfoCircle, FaTimes, FaExclamationTriangle } from 'react-icons/fa';

const Toast = ({ message, type = 'info', onClose, duration = 5000 }) => {
    const [isVisible, setIsVisible] = useState(false);
    const [isLeaving, setIsLeaving] = useState(false);

    useEffect(() => {
        // Trigger enter animation
        setTimeout(() => setIsVisible(true), 10);
    }, []);

    const handleClose = () => {
        setIsLeaving(true);
        setTimeout(() => {
            onClose?.();
        }, 300);
    };

    const getIcon = () => {
        switch (type) {
            case 'success':
                return <FaCheckCircle className="w-5 h-5 text-green-500" />;
            case 'error':
                return <FaExclamationCircle className="w-5 h-5 text-red-500" />;
            case 'warning':
                return <FaExclamationTriangle className="w-5 h-5 text-yellow-500" />;
            default:
                return <FaInfoCircle className="w-5 h-5 text-blue-500" />;
        }
    };

    const getBackgroundColor = () => {
        switch (type) {
            case 'success':
                return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            case 'error':
                return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
            case 'warning':
                return 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
            default:
                return 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
        }
    };

    return (
        <div
            className={`
                ${getBackgroundColor()}
                border rounded-lg shadow-lg p-4 pr-12
                transform transition-all duration-300 ease-out
                ${isVisible && !isLeaving ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'}
                relative
            `}
            role="alert"
            aria-live="polite"
        >
            <div className="flex items-start gap-3">
                <div className="flex-shrink-0 mt-0.5">
                    {getIcon()}
                </div>
                <div className="flex-1">
                    <p className="text-sm font-medium text-gray-800 dark:text-gray-200">
                        {message}
                    </p>
                </div>
            </div>
            <button
                onClick={handleClose}
                className="absolute top-3 right-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                aria-label="Close notification"
            >
                <FaTimes className="w-4 h-4" />
            </button>
        </div>
    );
};

export default Toast;
