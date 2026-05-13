import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import classnames from 'classnames';
import Image from "../components/Image";

type Message = {
    author: string;
    avatar: string;
    parts: string[];
    date: string;
    opposite?: boolean;
    delivered?: boolean;
};

type Section = {
    title: string;
    messages: Message[];
};

export default function() {
    const svg = useSvg();
    const imageSize = 64;

    const contactsData = [
        {
            avatar: 'images/customers/customer-1.jpg',
            name: 'Jessica Moore',
            status: 'offline',
            excerpt: 'Historically, philosophy encompassed all bodies of knowledge and a practitioner was known as a philosopher.',
            date: '2 minutes',
        },
        {
            avatar: 'images/customers/customer-2.jpg',
            name: 'Adam Taylor',
            status: 'online',
            excerpt: 'In the 19th century, the growth of modern research universities led academic philosophy and other disciplines to professionalize and specialize.',
            date: '2 hours',
        },
        {
            avatar: 'images/customers/customer-3.jpg',
            name: 'Helena Garcia',
            status: 'offline',
            excerpt: 'Philosophical methods include questioning, critical discussion, rational argument, and systematic presentation.',
            date: '5 hours',
        },
        {
            avatar: 'images/customers/customer-5.jpg',
            name: 'Olivia Smith',
            status: 'away',
            excerpt: 'In this sense, philosophy is closely related to religion, mathematics, natural science, education, and politics.',
            date: '2 days',
        },
        {
            avatar: 'images/customers/customer-6.jpg',
            name: 'Kevin Smith',
            status: 'offline',
            excerpt: 'This division is not obsolete, but has changed: natural philosophy has split into the various natural sciences.',
            date: '13 hours',
        },
        {
            avatar: 'images/customers/customer-7.jpg',
            name: 'Brian Wood',
            status: 'offline',
            excerpt: 'Western philosophy is the philosophical tradition of the Western world, dating back to pre-Socratic thinkers who were active in 6th-century.',
            date: '43 minutes',
        },
        {
            avatar: 'images/customers/customer-8.jpg',
            name: 'Ethan Young',
            status: 'offline',
            excerpt: 'Early modern philosophy in the Western world begins with thinkers such as Thomas Hobbes.',
            date: '3 weeks',
        },
        {
            avatar: 'images/customers/customer-9.jpg',
            name: 'Charlotte Jones',
            status: 'offline',
            excerpt: 'Major modern philosophers include Spinoza, Leibniz, Locke, Berkeley, Hume, and Kant.',
            date: '21 hour',
        },
        {
            avatar: 'images/customers/customer-10.jpg',
            name: 'Isabel Williams',
            status: 'offline',
            excerpt: 'Islamic philosophy is the philosophical work originating in the Islamic tradition and is mostly done in Arabic.',
            date: '5 years',
        },
        {
            avatar: 'images/customers/customer-11.jpg',
            name: 'Jacob Lee',
            status: 'offline',
            excerpt: 'Babylonian astronomy also included much philosophical speculations about cosmology which may have influenced the Ancient Greeks.',
            date: '25 minutes',
        },
        {
            avatar: 'images/customers/customer-12.jpg',
            name: 'Anna Wilson',
            status: 'offline',
            excerpt: 'Philosophical questions can be grouped into various branches.',
            date: '4 hours',
        },
    ];

    const contacts = contactsData.map((contact, contactIdx) => (
        <li key={contactIdx} className="sa-chat__contact">
            <div className={`sa-chat__contact-avatar sa-symbol sa-symbol--status--${contact.status} sa-symbol--shape--circle`}>
                <Image src={contact.avatar} size={imageSize} />
                <div className="sa-symbol__status" />
            </div>
            <div className="sa-chat__contact-name">{contact.name}</div>
            <div className="sa-chat__contact-meta">{contact.excerpt}</div>
            <div className="sa-chat__contact-date">{contact.date}</div>
        </li>
    ));

    const sections: Section[] = [
        {
            title: '7 October',
            messages: [
                {
                    author: 'Adam Taylor',
                    avatar: 'images/customers/customer-2.jpg',
                    parts: [
                        'Hello',
                        'What do you think about this?',
                        'In the 19th century, the growth of modern research universities led academic philosophy and other disciplines to professionalize and specialize. Since then, various areas of investigation that were traditionally part of philosophy have become separate academic disciplines, such as psychology.',
                    ],
                    date: '18:46',
                    opposite: true,
                },
                {
                    author: 'Ryan Ford',
                    avatar: 'images/customers/customer-4.jpg',
                    parts: [
                        'Well',
                        'Basically this looks good',
                        'Can you suggest other variants?',
                    ],
                    date: '18:46',
                    delivered: true,
                },
                {
                    author: 'Adam Taylor',
                    avatar: 'images/customers/customer-2.jpg',
                    parts: [
                        'Yes, sure',
                        'These groupings allow philosophers to focus on a set of similar topics and interact with other thinkers who are interested in the same questions.',
                    ],
                    date: '18:46',
                    opposite: true,
                },
                {
                    author: 'Ryan Ford',
                    avatar: 'images/customers/customer-4.jpg',
                    parts: [
                        'Sorry, but I need to run, let\'s discuss this later',
                        'I\'ll write when I\'m done.',
                    ],
                    date: '18:46',
                    delivered: true,
                },
                {
                    author: 'Adam Taylor',
                    avatar: 'images/customers/customer-2.jpg',
                    parts: [
                        'Well I will wait',
                    ],
                    date: '18:46',
                    opposite: true,
                },
            ],
        },
        {
            title: '1 January',
            messages: [
                {
                    author: 'Ryan Ford',
                    avatar: 'images/customers/customer-4.jpg',
                    parts: [
                        'Hi Adam',
                        'I am back and ready for further discussions',
                    ],
                    date: '18:46',
                    delivered: true,
                },
                {
                    author: 'Adam Taylor',
                    avatar: 'images/customers/customer-2.jpg',
                    parts: [
                        'Metaphysics is the study of the most general features of reality, such as existence, time, objects and their properties, wholes and their parts, events, processes and causation and the relationship between mind and body.',
                        'What about Metaphysics?',
                    ],
                    date: '18:46',
                    opposite: true,
                },
                {
                    author: 'Ryan Ford',
                    avatar: 'images/customers/customer-4.jpg',
                    parts: [
                        'Hmm',
                        'No, Metaphysics is a bad idea',
                    ],
                    date: '18:46',
                    delivered: true,
                },
            ],
        },
    ];

    const content = sections.map((section, sectionIdx) => (
        <React.Fragment key={sectionIdx}>
            <div className="sa-chat__divider">{section.title}</div>

            {section.messages.map((message, messageIdx) => (
                <div key={messageIdx} className={classnames('sa-chat__message', {'sa-chat__message--opposite': message.opposite})}>
                    <div className="sa-chat__message-avatar">
                        <div className="sa-symbol sa-symbol--shape--circle">
                            <Image src={message.avatar} size={imageSize} />
                        </div>
                    </div>
                    <div className="sa-chat__message-parts">
                        {message.parts.map((part, partIdx) => (
                            <div key={partIdx} className="sa-chat__message-part dropdown">
                                <div className="sa-chat__message-text">{part}</div>
                                <button
                                    className="sa-chat__message-actions"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    aria-label="More"
                                >
                                    {svg('feather/more-vertical')}
                                </button>
                                <ul className="dropdown-menu" aria-label="Chat message context menu">
                                    <li><a className="dropdown-item" href="#">Reply</a></li>
                                    <li><a className="dropdown-item" href="#">Copy Text</a></li>
                                    <li><a className="dropdown-item" href="#">Forward Message</a></li>
                                    <li><hr className="dropdown-divider" /></li>
                                    <li><a className="dropdown-item text-danger" href="#">Delete</a></li>
                                </ul>
                            </div>
                        ))}
                    </div>
                    <div className="sa-chat__message-time">
                        {message.author} – {message.date}
                        {message.delivered && ' - Delivered'}
                    </div>
                </div>
            ))}

        </React.Fragment>
    ))

    return (
        <Layout>
            <App showFooter={false} bodyClassName="d-flex">
                <div className="sa-chat sa-chat--open flex-grow-1">
                    <div className="sa-chat__sidebar">
                        <div className="sa-chat__header">
                            <div className="sa-chat__header-avatar sa-symbol sa-symbol--shape--circle">
                                <Image src="images/customers/customer-4.jpg" size={imageSize} />
                            </div>

                            <input
                                type="text"
                                placeholder="Search over contacts"
                                className="form-control form-control--search"
                            />
                        </div>
                        <ul className="sa-chat__contacts" data-simplebar="">
                            {contacts}
                        </ul>
                    </div>
                    <div className="sa-chat__main">
                        <div className="sa-chat__header">
                            <button type="button" className="btn btn-sa-muted sa-chat__header-back">
                                {svg('feather/arrow-left')}
                            </button>

                            <div className="sa-chat__header-avatar sa-symbol sa-symbol--status--online sa-symbol--shape--circle">
                                <Image src="images/customers/customer-2.jpg" size={imageSize} />
                                <div className="sa-symbol__status" />
                            </div>
                            <div className="sa-chat__header-info">
                                <div className="sa-chat__header-title">
                                    Adam Taylor
                                </div>
                                <div className="sa-chat__header-meta">
                                    Last seen 7 days ago
                                </div>
                            </div>
                            <div className="sa-chat__header-actions">
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                    data-bs-toggle="tooltip"
                                    title="Call"
                                >
                                    {svg('feather/phone')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-sa-muted btn-sa-icon fs-exact-20"
                                    data-bs-toggle="tooltip"
                                    title="More"
                                >
                                    {svg('feather/more-vertical')}
                                </button>
                            </div>
                        </div>
                        <div className="sa-chat__messages">
                            {content}
                        </div>
                        <form className="sa-chat__form d-flex">
                            <input type="text" placeholder="Hello, my name is Max" className="form-control"/>
                            <button className="btn btn-primary ms-3">
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
