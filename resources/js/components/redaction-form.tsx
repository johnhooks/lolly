import React from 'react';

import {
    Button,
    TextControl,
    SelectControl,
    CheckboxControl,
    __experimentalHStack as HStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface RedactionItem {
    type: string;
    value: string;
    remove?: boolean;
}

interface RedactionFormProps {
    redactions: RedactionItem[];
    onUpdate: (redactions: RedactionItem[]) => void;
}

const REDACTION_TYPES = [
    { label: __('All (*)', 'lolly'), value: '*' },
    { label: __('Query Params', 'lolly'), value: 'query' },
    { label: __('Headers', 'lolly'), value: 'header' },
    { label: __('Request Body', 'lolly'), value: 'request' },
    { label: __('Response Body', 'lolly'), value: 'response' },
];

export default function RedactionForm({
    redactions,
    onUpdate,
}: RedactionFormProps): React.ReactNode {
    const updateRedaction = (
        index: number,
        field: keyof RedactionItem,
        value: string | boolean
    ) => {
        const newRedactions = redactions.map((redaction, i) =>
            i === index ? { ...redaction, [field]: value } : redaction
        );
        onUpdate(newRedactions);
    };

    const removeRedaction = (index: number) => {
        const newRedactions = redactions.filter((_, i) => i !== index);
        onUpdate(newRedactions);
    };

    const addRedaction = () => {
        const newRedactions = [
            ...redactions,
            { type: '*', value: '', remove: false },
        ];
        onUpdate(newRedactions);
    };

    if (redactions.length === 0) {
        return (
            <div>
                <h4>{__('Redactions', 'lolly')}</h4>
                <p style={{ fontStyle: 'italic', color: '#666' }}>
                    {__('No redactions configured.', 'lolly')}
                </p>
                <Button variant="secondary" onClick={addRedaction}>
                    {__('Add Redaction', 'lolly')}
                </Button>
            </div>
        );
    }

    return (
        <div>
            <h4>{__('Redactions', 'lolly')}</h4>
            <table
                style={{
                    width: '100%',
                    borderCollapse: 'collapse',
                    marginBottom: '12px',
                }}
            >
                <thead>
                    <tr style={{ backgroundColor: '#f6f7f7' }}>
                        <th
                            style={{
                                padding: '8px 12px',
                                textAlign: 'left',
                                borderBottom: '2px solid #ddd',
                                fontWeight: 600,
                            }}
                        >
                            {__('Type', 'lolly')}
                        </th>
                        <th
                            style={{
                                padding: '8px 12px',
                                textAlign: 'left',
                                borderBottom: '2px solid #ddd',
                                fontWeight: 600,
                            }}
                        >
                            {__('Value', 'lolly')}
                        </th>
                        <th
                            style={{
                                padding: '8px 12px',
                                textAlign: 'center',
                                borderBottom: '2px solid #ddd',
                                fontWeight: 600,
                            }}
                        >
                            {__('Remove', 'lolly')}
                        </th>
                        <th
                            style={{
                                padding: '8px 12px',
                                textAlign: 'center',
                                borderBottom: '2px solid #ddd',
                                fontWeight: 600,
                                width: '100px',
                            }}
                        >
                            {__('Actions', 'lolly')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {redactions.map((redaction, redactionIndex) => (
                        <tr
                            key={redactionIndex}
                            style={{
                                borderBottom: '1px solid #ddd',
                            }}
                        >
                            <td
                                style={{
                                    padding: '8px 12px',
                                    verticalAlign: 'top',
                                }}
                            >
                                <SelectControl
                                    value={redaction.type}
                                    options={REDACTION_TYPES}
                                    onChange={(value) =>
                                        updateRedaction(
                                            redactionIndex,
                                            'type',
                                            value
                                        )
                                    }
                                    __nextHasNoMarginBottom
                                />
                            </td>
                            <td
                                style={{
                                    padding: '8px 12px',
                                    verticalAlign: 'top',
                                }}
                            >
                                <TextControl
                                    value={redaction.value}
                                    onChange={(value) =>
                                        updateRedaction(
                                            redactionIndex,
                                            'value',
                                            value
                                        )
                                    }
                                    placeholder="password, token, etc."
                                    __nextHasNoMarginBottom
                                />
                            </td>
                            <td
                                style={{
                                    padding: '8px 12px',
                                    textAlign: 'center',
                                    verticalAlign: 'top',
                                }}
                            >
                                <HStack alignment="center">
                                    <CheckboxControl
                                        checked={redaction.remove || false}
                                        onChange={(value) =>
                                            updateRedaction(
                                                redactionIndex,
                                                'remove',
                                                value
                                            )
                                        }
                                        __nextHasNoMarginBottom
                                    />
                                </HStack>
                            </td>
                            <td
                                style={{
                                    padding: '8px 12px',
                                    textAlign: 'center',
                                    verticalAlign: 'top',
                                }}
                            >
                                <Button
                                    isDestructive
                                    variant="tertiary"
                                    size="small"
                                    onClick={() =>
                                        removeRedaction(redactionIndex)
                                    }
                                >
                                    {__('Remove', 'lolly')}
                                </Button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <Button variant="secondary" onClick={addRedaction}>
                {__('Add Redaction', 'lolly')}
            </Button>
        </div>
    );
}
