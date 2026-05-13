import React from "react";
import classnames from "classnames";

type Props = {
    title: string;
    actions?: React.ReactNode;
    breadcrumb?: Array<{
        title: string;
        url: string;
    }>;
};

export default function({title, actions, breadcrumb}: Props) {
    return (
        <div className="py-5">
            <div className="row g-4 align-items-center">
                <div className="col">
                    {breadcrumb && (
                        <nav className="mb-2" aria-label="breadcrumb">
                            <ol className="breadcrumb breadcrumb-sa-simple">
                                {breadcrumb.map((item, itemIdx) => {
                                    const isLast = itemIdx === breadcrumb.length - 1;

                                    return (
                                        <li
                                            key={itemIdx}
                                            className={classnames('breadcrumb-item', {active: isLast})}
                                            aria-current={isLast ? 'page' : undefined}
                                        >
                                            {isLast && item.title}
                                            {!isLast && <a href={item.url}>{item.title}</a>}
                                        </li>
                                    );
                                })}
                            </ol>
                        </nav>
                    )}
                    <h1 className="h3 m-0">{title}</h1>
                </div>
                {actions && (
                    <div className="col-auto d-flex">
                        {actions}
                    </div>
                )}
            </div>
        </div>
    );
}
