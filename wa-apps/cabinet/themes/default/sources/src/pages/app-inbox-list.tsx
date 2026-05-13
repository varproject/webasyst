import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classnames from "classnames";
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import Inbox from "../components/Inbox";
import { url } from "../components/utils";

type Message = {
    author: {
        symbol: string;
        name: string;
    };
    subject: string;
    excerpt: string;
    date: string;
    labels?: {
        style: string;
        label: string;
    }[];
    unread?: boolean;
    hasAttachments?: boolean;
    starred?: boolean;
};

export default function() {
    const svg = useSvg();
    const message: Message[] = [
        {
            author: {
                symbol: 'AT',
                name: 'Adam Taylor',
            },
            subject: 'In the 19th century, the growth of modern research.',
            excerpt: 'Universities led academic philosophy and other disciplines to professionalize and specialize',
            date: '20 Dec',
        },
        {
            author: {
                symbol: 'OS',
                name: 'Olivia Smith'
            },
            subject: 'Since then, various areas of investigation.',
            excerpt: 'That were traditionally part of philosophy have become separate academic disciplines, such as psychology, sociology, linguistics, and economics.',
            date: '28 Nov',
            labels: [
                {label: 'Stroyka/HTML', style: 'sa-primary'},
            ],
            unread: true,
            hasAttachments: true,
        },
        {
            author: {
                symbol: 'KS',
                name: 'Kevin Smith',
            },
            subject: 'Initially the term referred to any body of knowledge.',
            excerpt: 'In this sense, philosophy is closely related to religion, mathematics, natural science, education, and politics.',
            date: '12 Nov',
            unread: true,
        },
        {
            author: {
                symbol: 'EY',
                name: 'Ethan Young'
            },
            subject: 'In one general sense, philosophy is associated with wisdom, intellectual culture, and a search for knowledge.',
            excerpt: 'In this sense, all cultures and literate societies ask philosophical questions, such as "how are we to live" and "what is the nature of reality."',
            date: '21 Oct',
            starred: true,
        },
        {
            author: {
                symbol: 'BW',
                name: 'Brian Wood',
            },
            subject: 'In Against the Logicians the Pyrrhonist philosopher.',
            excerpt: 'Sextus Empiricus detailed the variety of ways in which the ancient Greek philosophers had divided philosophy, noting that this three-part division was agreed to by Plato, Aristotle, Xenocrates, and the Stoics.',
            date: '7 Oct',
            hasAttachments: true,
        },
        {
            author: {
                symbol: 'JM',
                name: 'Jessica Moore'
            },
            subject: 'Medieval philosophy is the period following the fall of the Western Roman Empire.',
            excerpt: 'Was dominated by the rise of Christianity and hence reflects Judeo-Christian theological concerns as well as retaining a continuity with Greco-Roman thought.',
            date: '30 Sep',
            labels: [
                {label: 'Payout', style: 'sa-warning'},
                {label: 'Important', style: 'sa-info'},
            ],
            starred: true,
        },
        {
            author: {
                symbol: 'HG',
                name: 'Helena Garcia',
            },
            subject: 'The regions of the Fertile Crescent, Iran and Arabia are home.',
            excerpt: 'To the earliest known philosophical wisdom literature and is today mostly dominated by Islamic culture.',
            date: '2 Sep',
        },
        {
            author: {
                symbol: 'AT',
                name: 'Adam Taylor'
            },
            subject: 'Early Wisdom Literature from the Fertile Crescent was a genre.',
            excerpt: 'Which sought to instruct people on ethical action, practical living and virtue through stories and proverbs.',
            date: '17 Aug',
        },
        {
            author: {
                symbol: 'CJ',
                name: 'Charlotte Jones',
            },
            subject: 'Islamic philosophy is the philosophical work originating in the Islamic tradition and is mostly done in Arabic.',
            excerpt: 'It draws from the religion of Islam as well as from Greco-Roman philosophy.',
            date: '30 Jul',
        },
        {
            author: {
                symbol: 'IW',
                name: 'Isabel Williams'
            },
            subject: 'Many philosophical debates that began in ancient times are still debated today.',
            excerpt: 'British philosopher Colin McGinn claims that no philosophical progress has occurred during that interval.',
            date: '27 Jun',
            labels: [
                {label: 'RedParts/HTML', style: 'sa-primary'},
            ],
        },

        {
            author: {
                symbol: 'HG',
                name: 'Helena Garcia',
            },
            subject: 'Metaphysics is the study of the most general features of reality, such as existence, time, objects and their properties.',
            excerpt: 'Wholes and their parts, events, processes and causation and the relationship between mind and body.',
            date: '13 Jul',
        },
        {
            author: {
                symbol: 'OS',
                name: 'Olivia Smith',
            },
            subject: 'A major point of debate is between realism.',
            excerpt: 'Which holds that there are entities that exist independently of their mental perception and idealism, which holds that reality is mentally constructed or otherwise immaterial. Metaphysics deals with the topic of identity.',
            date: '4 Jul',
            labels: [
                {label: 'Feature request', style: 'sa-secondary'},
                {label: 'Stroyka/HTML', style: 'sa-primary'},
            ],
        },
        {
            author: {
                symbol: 'EY',
                name: 'Ethan Young',
            },
            subject: 'Logic is the study of reasoning and argument.',
            excerpt: 'Deductive reasoning is when, given certain premises, conclusions are unavoidably implied.',
            date: '20 Jun',
        },
        {
            author: {
                symbol: 'IS',
                name: 'Isabel Williams',
            },
            subject: 'Philosophy of language explores the nature, origins, and use of language.',
            excerpt: 'Philosophy of mind explores the nature of the mind and its relationship to the body, as typified by disputes between materialism and dualism.',
            date: '5 May',
            labels: [
                {label: 'Security', style: 'sa-danger'},
            ],
        },
        {
            author: {
                symbol: 'BW',
                name: 'Brian Wood',
            },
            subject: 'The philosophy of science explores the foundations, methods, history, implications and purpose of science.',
            excerpt: 'Many of its subdivisions correspond to specific branches of science.',
            date: '4 May',
        },
        {
            author: {
                symbol: 'RF',
                name: 'Ryan Ford',
            },
            subject: 'Political philosophy is the study of government and the relationship of individuals (or families and clans) to communities including the state.',
            excerpt: 'It includes questions about justice, law, property and the rights and obligations of the citizen.',
            date: '30 Apr',
        },
        {
            author: {
                symbol: 'CJ',
                name: 'Charlotte Jones',
            },
            subject: 'Issues include the existence of God, the relationship between reason and faith, questions of religious epistemology, the relationship between religion and science.',
            excerpt: 'How to interpret religious experiences, questions about the possibility of an afterlife, the problem of religious language and the existence of souls and responses to religious pluralism and diversity.',
            date: '16 Apr',
        },
        {
            author: {
                symbol: 'JM',
                name: 'Jessica Moore',
            },
            subject: 'Metaphilosophy explores the aims, boundaries and methods of philosophy.',
            excerpt: 'It is debated as to whether Metaphilosophy is a subject that comes prior to philosophy or whether it is inherently part of philosophy.',
            date: '12 Mar',
            labels: [
                {label: 'Important', style: 'sa-info'},
            ],
        },
        {
            author: {
                symbol: 'BW',
                name: 'Brian Wood',
            },
            subject: 'Some of those who study philosophy become professional philosophers, typically by working as professors who teach, research and write in academic institutions.',
            excerpt: 'However, most students of academic philosophy later contribute to law, journalism, religion, sciences, politics, business, or various arts.',
            date: '22 Feb',
        },
        {
            author: {
                symbol: 'JL',
                name: 'Jacob Lee',
            },
            subject: 'Recent efforts to avail the general public to the work and relevance of philosophers include the million-dollar Berggruen Prize, first awarded to Charles Taylor in 2016.',
            excerpt: 'Some philosophers argue that this professionalization has negatively affected the discipline.',
            date: '3 Jan',
        },
    ];

    const rows = message.map((message, messageIdx) => (
        <div
            key={messageIdx}
            className={classnames('sa-inbox-list__item', {'sa-inbox-list__item--unread': message.unread, 'sa-inbox-list__item--starred': message.starred})}
        >
            <label className="sa-inbox-list__checkbox">
                <input type="checkbox" className="form-check-input m-0 d-block" aria-label="..." />
            </label>
            <div className="sa-inbox-list__star">
                {svg('feather/star')}
            </div>

            <div className="sa-inbox-list__author">
                <div className="sa-symbol sa-symbol--shape--circle">
                    <div className="sa-symbol__text">{message.author.symbol}</div>
                </div>
                {message.author.name}
            </div>
            <div className="sa-inbox-list__summary">
                {message.labels?.length > 0 && (
                    <div className="sa-inbox-list__badges">
                        {message.labels.map((label, labelIdx) => (
                            <span key={labelIdx} className={`badge badge-${label.style}`}>{label.label}</span>
                        ))}
                    </div>
                )}

                <div className="sa-inbox-list__content">
                    <span className="sa-inbox-list__subject">
                        <a href={url('inbox-conversation')}>{message.subject}</a>
                    </span>
                    {' – '}
                    <span className="sa-inbox-list__title">{message.excerpt}</span>
                </div>
            </div>
            {message.hasAttachments && (
                <div className="sa-inbox-list__attachments">
                    {svg('feather/paperclip')}
                </div>
            )}
            <div className="sa-inbox-list__date">
                {message.date}
            </div>
        </div>
    ));

    return (
        <Layout>
            <App showFooter={false} bodyClassName="d-flex">
                <Inbox className="flex-grow-1">
                    <div className="sa-inbox-toolbar">
                        <label className="sa-inbox-toolbar__checkbox">
                            <input type="checkbox" className="form-check-input" aria-label="..." />

                            Select All
                        </label>
                        <button type="button" className="btn btn-sa-muted btn-sa-icon fs-exact-20 sa-inbox-toolbar__menu">
                            {svg('stroyka/hamburger-20')}
                        </button>
                        <div className="flex-grow-1" />
                        <div className="sa-inbox-toolbar__text">
                            1–32 of 512
                        </div>
                        <button type="button" className="btn btn-sa-muted btn-sa-icon fs-exact-20">
                            {svg('feather/chevron-left')}
                        </button>
                        <button type="button" className="btn btn-sa-muted btn-sa-icon fs-exact-20">
                            {svg('feather/chevron-right')}
                        </button>
                        <button type="button" className="btn btn-sa-muted btn-sa-icon fs-exact-20 me-n3">
                            {svg('feather/more-vertical')}
                        </button>
                        <div className="me-n2" />
                    </div>
                    <div className="sa-inbox-list">
                        {rows}
                    </div>
                </Inbox>
            </App>
        </Layout>
    );
}
