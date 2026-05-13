import React, { PropsWithChildren } from "react";
import classnames from "classnames";
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";

type Section = {
    title?: string;
    items: {
        title: string;
        current?: boolean;
        icon?: string;
        color?: string;
        badge?: {
            style: string;
            content: string;
        };
    }[];
};

type Props = PropsWithChildren<{
    className?: string;
}>;

export default function({children, className}: Props) {
    const svg = useSvg();
    const sections: Section[] = [
        {
            items: [
                {
                    title: 'Inbox',
                    icon: 'feather/inbox',
                    current: true,
                    badge: {
                        style: 'sa-primary',
                        content: '8',
                    },
                },
                {title: 'Snoozed', icon: 'feather/clock'},
                {
                    title: 'Collaboration',
                    icon: 'feather/bookmark',
                    badge: {
                        style: 'sa-primary',
                        content: '2',
                    },
                },
                {title: 'RedParts', icon: 'feather/send'},
            ],
        },
        {
            title: 'Labels',
            items: [
                {title: 'Extended Support', icon: 'stroyka/circle-20', color: '#fa3939'},
                {title: 'Reviews', icon: 'stroyka/circle-20', color: '#3562ff'},
                {title: 'Typical Solutions', icon: 'stroyka/circle-20', color: '#53a700'},
                {title: 'Want WordPress', icon: 'stroyka/circle-20', color: '#8939c8'},
            ],
        },
    ];

    const sidebar = (
        <ul className="sa-nav sa-nav--card">
            {sections.map((section, sectionIdx) => (
                <li key={sectionIdx} className="sa-nav__section">
                    {section.title && (
                        <div className="sa-nav__section-title">
                            <span>{section.title}</span>
                        </div>
                    )}

                    <ul className="sa-nav__menu">
                        {section.items.map((item, itemIdx) => (
                            <li key={itemIdx} className={classnames('sa-nav__menu-item', {'sa-nav__menu-item--active': item.current, 'sa-nav__menu-item--has-icon': item.icon})}>
                                <a href="#" className="sa-nav__link">
                                    {item.icon && (
                                        <span className="sa-nav__icon" style={{color: item.color}}>{svg(item.icon)}</span>
                                    )}

                                    <span className="sa-nav__title">{item.title}</span>

                                    {item.badge && (
                                        <span className={`sa-nav__badge badge badge-sa-pill badge-${item.badge.style}`}>
                                            {item.badge.content}
                                        </span>
                                    )}
                                </a>
                            </li>
                        ))}
                    </ul>
                </li>
            ))}
        </ul>
    );

    return (
        <>
            <div className={classnames('sa-inbox', className)}>
                <div className="sa-inbox__backdrop" />
                <div className="sa-inbox__sidebar" data-simplebar="">
                    <button
                        type="button"
                        className="btn btn-primary sa-inbox__compose-button"
                        data-bs-toggle="modal"
                        data-bs-target="#composeModal"
                    >Compose</button>

                    {sidebar}
                </div>
                <div className="sa-inbox__body">
                    {children}
                </div>
            </div>
            <div className="modal fade" id="composeModal" tabIndex={-1} aria-labelledby="composeModalLabel" aria-hidden="true">
                <div className="modal-dialog modal-dialog-centered modal-lg">
                    <form className="modal-content">
                        <div className="modal-header px-5">
                            <h5 className="modal-title" id="composeModalLabel">New message</h5>
                            <button
                                type="button"
                                className="sa-close sa-close--modal"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            />
                        </div>
                        <div className="modal-body p-5">
                            <div className="mb-4">
                                <label htmlFor="form-compose/email" className="visually-hidden">Email address</label>
                                <input
                                    type="email"
                                    className="form-control"
                                    id="form-compose/email"
                                    placeholder="stroyka@example.com"
                                    readOnly
                                />
                            </div>
                            <div className="mb-4 d-flex align-items-center">
                                <label htmlFor="form-compose/to" className="visually-hidden">To</label>
                                <input
                                    type="email"
                                    className="form-control"
                                    id="form-compose/to"
                                    placeholder="To"
                                />
                                <div className="ms-4">
                                    <a href="#" className="text-muted fs-exact-13">CC</a>
                                </div>
                                <div className="ms-4">
                                    <a href="#" className="text-muted fs-exact-13">BCC</a>
                                </div>
                            </div>
                            <div className="mb-4">
                                <label htmlFor="form-compose/subject" className="visually-hidden">Subject</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="form-compose/subject"
                                    placeholder="Subject"
                                />
                            </div>
                            <div className="mb-4">
                                <textarea className="sa-quill-control form-control" rows={8} placeholder="Message" />
                            </div>
                            <div className="row g-3">
                                <div className="col-sm-auto col-12">
                                    <button type="submit" className="btn btn-primary w-100">
                                        Send
                                    </button>
                                </div>
                                <div className="col d-flex flex-wrap">
                                    <button
                                        type="button"
                                        className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                        data-bs-toggle="tooltip"
                                        title="Attache File"
                                    >
                                        {svg('feather/paperclip')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                        data-bs-toggle="tooltip"
                                        title="Insert Image"
                                    >
                                        {svg('feather/image')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                        data-bs-toggle="tooltip"
                                        title="Insert Link"
                                    >
                                        {svg('feather/link')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                        data-bs-toggle="tooltip"
                                        title="Insert Emotion"
                                    >
                                        {svg('feather/smile')}
                                    </button>

                                    <div className="flex-grow-1" />

                                    <button
                                        type="button"
                                        className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                        data-bs-toggle="tooltip"
                                        title="More"
                                    >
                                        {svg('feather/more-vertical')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                        data-bs-toggle="tooltip"
                                        title="Delete"
                                    >
                                        {svg('feather/trash-2')}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
