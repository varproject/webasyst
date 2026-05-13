import React from 'react';

export interface BreadcrumbItem {
    title: string;
    url?: string;
}

interface Props {
    items: BreadcrumbItem[];
    className?: string;
}

export default function(props: Props) {
    const { items, className = '' } = props;

    return (
        <nav className={className} aria-label="breadcrumb">
            <ol className="breadcrumb breadcrumb-sa-simple">
                {items.map((item, itemIdx) => {
                    const isLink = itemIdx < items.length - 1 && item.url !== undefined;

                    return (
                        <React.Fragment key={itemIdx}>
                            {isLink && (
                                <li className="breadcrumb-item">
                                    <a href={item.url}>{item.title}</a>
                                </li>
                            )}
                            {!isLink && (
                                <li className="breadcrumb-item active" aria-current="page">
                                    {item.title}
                                </li>
                            )}
                        </React.Fragment>
                    );
                })}

            </ol>
        </nav>
    );
}
