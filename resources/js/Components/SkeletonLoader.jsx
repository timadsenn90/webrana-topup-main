import React from 'react';

const SkeletonLoader = ({ type = 'card', count = 1 }) => {
    const renderSkeleton = () => {
        switch (type) {
            case 'card':
                return (
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700 animate-pulse">
                        <div className="h-40 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4"></div>
                        <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                        <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                );
            
            case 'list':
                return (
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700 animate-pulse">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                            <div className="flex-1">
                                <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                                <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                            </div>
                        </div>
                    </div>
                );
            
            case 'text':
                return (
                    <div className="animate-pulse space-y-2">
                        <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                        <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-4/6"></div>
                    </div>
                );
            
            case 'table':
                return (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="p-4 border-b border-gray-200 dark:border-gray-700 animate-pulse">
                            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                        </div>
                        {[...Array(5)].map((_, i) => (
                            <div key={i} className="p-4 border-b border-gray-200 dark:border-gray-700 animate-pulse">
                                <div className="flex gap-4">
                                    <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                                    <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                                    <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6"></div>
                                </div>
                            </div>
                        ))}
                    </div>
                );
            
            default:
                return null;
        }
    };

    return (
        <div className="space-y-4">
            {[...Array(count)].map((_, i) => (
                <div key={i}>{renderSkeleton()}</div>
            ))}
        </div>
    );
};

export default SkeletonLoader;
