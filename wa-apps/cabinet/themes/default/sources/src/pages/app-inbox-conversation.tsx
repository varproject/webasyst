import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classnames from "classnames";
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import Inbox from "../components/Inbox";
import Image from "../components/Image";
import { url } from "../components/utils";

type Message = {
    author: {
        name: string;
        avatar: string;
    },
    date: string;
    recipient: string;
    excerpt: string;
    expanded?: boolean;
    attachments?: {
        name: string;
        size: string;
    }[];
    content: string;
};

export default function() {
    const svg = useSvg();
    const imageSize = 64;
    const title = '[ThemeForest] Message sent via your Envato Market profile from kos9';
    const messages: Message[] = [
        {
            author: {
                name: 'Envato Market',
                avatar: 'images/customers/customer-7.jpg',
            },
            date: '5 Oct 2020, 2:43',
            recipient: 'Konstantin Veselovsky',
            excerpt: 'Hi Kos9, We wanted to let you know that your payout for earnings up until the end of July 2021 has been calculated. Your payout will be $320.00. Your payout will be processed as part of our normal schedule later this month. You will receive an email when your payout has been processed.',
            content: ''
                + 'Hi Kos9,<br>'
                + '<br>'
                + 'We wanted to let you know that your payout for earnings up until the end of July 2021 has been calculated.<br>'
                + '<br>'
                + 'Your payout will be <strong>$320.00</strong>.<br>'
                + '<br>'
                + 'Your payout will be processed as part of our normal schedule later this month. You will receive an email when your payout has been processed.<br>'
                + '<br>'
                + 'Regards,<br>'
                + 'Envato Market Team',
        },
        {
            author: {
                name: 'Konstantin Veselovsky',
                avatar: 'images/customers/customer-4.jpg',
            },
            date: '7 Oct 2020, 10:25',
            recipient: 'Envato Market',
            excerpt: 'Hello how are you This is nowt a wordpres websie thems . i want to install it on wordpres admin interface can i do it? --------- This email was sent through your item support contact form on Envato Market. Item: Stroyka - Tools Store React eCommerce Template Sender: xhunedi',
            expanded: true,
            content: ''
                + 'Hello,<br>'
                + '<br>'
                + 'Philosophy is the study of general and fundamental questions, such as those about existence, reason, knowledge, values, mind, and language. Such questions are often posed as problems to be studied or resolved.'
                + 'Some sources claim the term was coined by Pythagoras, others dispute this story, arguing that Pythagoreans merely claimed use of a preexisting term.'
                + 'Philosophical methods include questioning, critical discussion, rational argument, and systematic presentation.<br>'
                + '<br>'
                + 'Historically, philosophy encompassed all bodies of knowledge and a practitioner was known as a philosopher.<br>'
                + '<br>'
                + 'Item: Stroyka - Tools Store React eCommerce Template<br>'
                + 'Sender: kos9<br>'
                + '<br>'
                + 'Date purchased: 26/10/2020<br>'
                + 'Support Entitlement: Support ends 26/04/2021<br>'
                + 'Verification URL (valid for 7 days): <a href="https://themeforest.net/user/kos9/portfolio">https://themeforest.net/user/kos9/portfolio</a><br>'
                + '<br>'
                + '<br>'
                + 'You can reply directly to this email to respond to kos9.',
            attachments: [
                {
                    name: 'Documentation.pdf',
                    size: '2.34 MB',
                },
                {
                    name: 'README.md',
                    size: '768 B',
                },
            ],
        },
        {
            author: {
                name: 'Envato Market',
                avatar: 'images/customers/customer-7.jpg',
            },
            date: '12 Oct 2020, 17:02',
            recipient: 'Konstantin Veselovsky',
            excerpt: 'Congratulations! Your update to RedParts - Auto Parts WordPress Theme on ThemeForest has been approved. Thanks for your submission! Regards, Envato Market Team',
            content: ''
                + 'Congratulations! Your update to RedParts - Auto Parts WordPress Theme on ThemeForest has been approved.<br>'
                + '<br>'
                + 'Thanks for your submission!<br>'
                + '<br>'
                + 'Regards,<br>'
                + 'Envato Market Team<br>',
        },
    ];

    const rows = messages.map((message, messageIdx) => (
        <div key={messageIdx} className={classnames('sa-inbox-chat__item', {'sa-inbox-chat__item--expanded': message.expanded})}>
            <div className="sa-inbox-chat__item-header">
                <div className="sa-inbox-chat__item-avatar">
                    <div className="sa-symbol sa-symbol--shape--circle">
                        <Image src={message.author.avatar} size={imageSize} />
                    </div>
                </div>
                <div className="sa-inbox-chat__item-author">{message.author.name}</div>
                <div className="sa-inbox-chat__item-date">{message.date}</div>
                <div className="sa-inbox-chat__item-actions">
                    <button
                        type="button"
                        className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-md-flex"
                        data-bs-toggle="tooltip"
                        title="Reply"
                    >
                        {svg('feather/corner-up-left')}
                    </button>
                    <button
                        type="button"
                        className="btn btn-sa-muted btn-sa-icon fs-exact-20 me-n3"
                        data-bs-toggle="tooltip"
                        title="More"
                    >
                        {svg('feather/more-vertical')}
                    </button>
                </div>
                <div className="sa-inbox-chat__item-meta">
                    <a href="#">To {message.recipient}</a>
                </div>
                <div className="sa-inbox-chat__item-excerpt">
                    {message.excerpt}
                </div>
            </div>
            <div className="sa-inbox-chat__item-body">
                <div className="sa-inbox-chat__item-message" dangerouslySetInnerHTML={{__html: message.content}} />
                {message.attachments?.length > 0 && (
                    <div className="sa-inbox-chat__item-attachments">
                        <ul>
                            {message.attachments.map((attachment, attachmentIdx) => (
                                <li key={attachmentIdx}>
                                    <a className="sa-inbox-chat__file" href="#">
                                        <div className="sa-inbox-chat__file-icon">
                                            {svg('feather/file-text')}
                                        </div>
                                        <div className="sa-inbox-chat__file-info">
                                            <div className="sa-inbox-chat__file-name">{attachment.name}</div>
                                            <div className="sa-inbox-chat__file-meta">{attachment.size}</div>
                                        </div>
                                    </a>
                                </li>
                            ))}
                        </ul>
                        <div>
                            <a href="#">Download All</a>
                        </div>
                    </div>
                )}
            </div>
        </div>
    ));

    return (
        <Layout>
            <App showFooter={false} bodyClassName="d-flex">
                <Inbox className="flex-grow-1">
                    <div className="sa-inbox-toolbar">
                        <a
                            href={url('inbox-list')}
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 sa-inbox-toolbar__back"
                            data-bs-toggle="tooltip"
                            title="Back"
                        >
                            {svg('feather/arrow-left')}
                        </a>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                            data-bs-toggle="tooltip"
                            title="Archive"
                        >
                            {svg('feather/archive')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                            data-bs-toggle="tooltip"
                            title="Spam"
                        >
                            {svg('feather/slash')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                            data-bs-toggle="tooltip"
                            title="Delete"
                        >
                            {svg('feather/trash')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                            data-bs-toggle="tooltip"
                            title="Mark As Unread"
                        >
                            {svg('feather/mail')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                            data-bs-toggle="tooltip"
                            title="Move To"
                        >
                            {svg('feather/folder')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                            data-bs-toggle="tooltip"
                            title="Labels"
                        >
                            {svg('feather/tag')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                            data-bs-toggle="tooltip"
                            title="More"
                        >
                            {svg('feather/more-vertical')}
                        </button>
                        <div className="flex-grow-1" />


                        <div className="sa-inbox-toolbar__text">
                            7 of 512
                        </div>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                            data-bs-toggle="tooltip"
                            title="Previous"
                        >
                            {svg('feather/chevron-left')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                            data-bs-toggle="tooltip"
                            title="Next"
                        >
                            {svg('feather/chevron-right')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-lg-none me-n3"
                            data-bs-toggle="tooltip"
                            title="More"
                        >
                            {svg('feather/more-vertical')}
                        </button>
                        <div className="me-n2" />
                    </div>
                    <div className="sa-inbox-chat">
                        <div className="sa-inbox-chat__header">
                            <div className="sa-inbox-chat__subject">{title}</div>
                            <div className="sa-inbox-chat__actions">
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                                    data-bs-toggle="tooltip"
                                    title="Print"
                                >
                                    {svg('feather/printer')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                                    data-bs-toggle="tooltip"
                                    title="Mark As Important"
                                >
                                    {svg('feather/star')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-lg-flex"
                                    data-bs-toggle="tooltip"
                                    title="Expand"
                                >
                                    {svg('feather/code')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-lg-none me-n3"
                                    data-bs-toggle="tooltip"
                                    title="More"
                                >
                                    {svg('feather/more-vertical')}
                                </button>
                                <div className="me-n2" />
                            </div>
                            <div className="sa-inbox-chat__labels">
                                <span className="badge badge-sa-primary">Stroyka Admin / HTML</span>
                                <span className="badge badge-sa-danger">Support Request</span>
                            </div>
                        </div>
                        <div className="sa-inbox-chat__list">
                            {rows}
                        </div>
                        <div className="sa-inbox-chat__form">
                            <div className="sa-symbol sa-symbol--shape--circle">
                                <Image src="images/customers/customer-4.jpg" size={imageSize} />
                            </div>
                            <div className="sa-inbox-chat__form-head">
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 me-3"
                                    data-bs-toggle="tooltip"
                                    title="Type of response"
                                >
                                    {svg('feather/corner-up-left')}
                                </button>
                                <input type="text" className="form-control" placeholder="To" />
                                <a href="#" className="ms-4 text-muted align-self-center fs-exact-13">CC</a>
                                <a href="#" className="ms-4 text-muted align-self-center fs-exact-13">BCC</a>
                            </div>

                            <textarea className="sa-quill-control form-control" rows={8} placeholder="Message" />

                            <div className="sa-inbox-chat__form-footer">
                                <button className="btn btn-primary">Send</button>

                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-sm-flex"
                                    data-bs-toggle="tooltip"
                                    title="Attache File"
                                >
                                    {svg('feather/paperclip')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-sm-flex"
                                    data-bs-toggle="tooltip"
                                    title="Insert Image"
                                >
                                    {svg('feather/image')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-sm-flex"
                                    data-bs-toggle="tooltip"
                                    title="Insert Link"
                                >
                                    {svg('feather/link')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20 d-none d-sm-flex"
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
                </Inbox>
            </App>
        </Layout>
    );
}
