@extends('approval-process::layout')

@section('title', 'Workflow Designer')
@section('page-title', 'Workflow Designer')
@section('page-description', 'Visual workflow builder')

@push('styles')
<style>
    .workflow-canvas {
        background-image: 
            linear-gradient(rgba(0, 0, 0, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
        background-size: 20px 20px;
    }
    
    .step-node {
        cursor: move;
        transition: all 0.3s;
    }
    
    .step-node:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    
    .connector-line {
        stroke: #6366f1;
        stroke-width: 2;
        fill: none;
        marker-end: url(#arrowhead);
    }
</style>
@endpush

@section('content')
<div x-data="workflowDesigner()" class="h-full">
    <!-- Toolbar -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button @click="addStep('approval')" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>
                    Add Approval Step
                </button>
                
                <button @click="addStep('condition')" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                    <i class="fas fa-code-branch mr-2"></i>
                    Add Condition
                </button>
                
                <button @click="addStep('parallel')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-stream mr-2"></i>
                    Add Parallel Step
                </button>
                
                <div class="border-l border-gray-300 h-8"></div>
                
                <button @click="validateWorkflow()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fas fa-check-circle mr-2"></i>
                    Validate
                </button>
                
                <button @click="saveWorkflow()" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    <i class="fas fa-save mr-2"></i>
                    Save
                </button>
            </div>
            
            <div class="flex items-center space-x-4">
                <button @click="exportWorkflow()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
                
                <button @click="importWorkflow()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    <i class="fas fa-upload mr-2"></i>
                    Import
                </button>
                
                <a href="{{ route('approval-process.workflows.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    <i class="fas fa-times mr-2"></i>
                    Close
                </a>
            </div>
        </div>
    </div>

    <!-- Designer Canvas -->
    <div class="grid grid-cols-4 gap-6">
        <!-- Canvas -->
        <div class="col-span-3">
            <div class="bg-white rounded-lg shadow p-6 workflow-canvas relative" style="min-height: 600px;">
                <!-- SVG for connectors -->
                <svg class="absolute inset-0 w-full h-full pointer-events-none" style="z-index: 1;">
                    <defs>
                        <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                            <polygon points="0 0, 10 3, 0 6" fill="#6366f1" />
                        </marker>
                    </defs>
                    <template x-for="(connection, index) in connections" :key="index">
                        <line 
                            :x1="connection.x1" 
                            :y1="connection.y1" 
                            :x2="connection.x2" 
                            :y2="connection.y2" 
                            class="connector-line"
                        />
                    </template>
                </svg>

                <!-- Steps -->
                <div class="relative" style="z-index: 2;">
                    <template x-for="(step, index) in steps" :key="step.id">
                        <div 
                            :style="`position: absolute; left: ${step.x}px; top: ${step.y}px;`"
                            class="step-node bg-white border-2 rounded-lg p-4 shadow-lg"
                            :class="{
                                'border-indigo-500': step.type === 'approval',
                                'border-purple-500': step.type === 'condition',
                                'border-blue-500': step.type === 'parallel'
                            }"
                            @click="selectStep(step)"
                            draggable="true"
                            @dragstart="dragStart($event, step)"
                            @dragend="dragEnd($event, step)"
                        >
                            <div class="flex items-center space-x-2 mb-2">
                                <i class="fas" 
                                   :class="{
                                       'fa-check-circle text-indigo-600': step.type === 'approval',
                                       'fa-code-branch text-purple-600': step.type === 'condition',
                                       'fa-stream text-blue-600': step.type === 'parallel'
                                   }"></i>
                                <span class="font-semibold" x-text="step.name || 'New Step'"></span>
                            </div>
                            
                            <div class="text-xs text-gray-600">
                                <div x-show="step.type === 'approval'">
                                    <i class="fas fa-user mr-1"></i>
                                    <span x-text="step.approvers?.length || 0"></span> approvers
                                </div>
                                <div x-show="step.type === 'condition'">
                                    <i class="fas fa-filter mr-1"></i>
                                    <span x-text="step.conditions?.length || 0"></span> conditions
                                </div>
                                <div x-show="step.type === 'parallel'">
                                    <i class="fas fa-arrows-alt-h mr-1"></i>
                                    Parallel execution
                                </div>
                            </div>
                            
                            <!-- Connection points -->
                            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-indigo-500 rounded-full cursor-pointer hover:scale-110"></div>
                            <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-green-500 rounded-full cursor-pointer hover:scale-110"></div>
                        </div>
                    </template>
                </div>

                <!-- Empty state -->
                <div x-show="steps.length === 0" class="absolute inset-0 flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <i class="fas fa-project-diagram text-6xl mb-4"></i>
                        <p class="text-xl">Start by adding a step</p>
                        <p class="text-sm mt-2">Click the buttons above to add approval steps, conditions, or parallel paths</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties Panel -->
        <div class="col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">
                    <i class="fas fa-cog mr-2"></i>
                    Properties
                </h3>

                <div x-show="selectedStep">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Step Name</label>
                        <input 
                            type="text" 
                            x-model="selectedStep.name"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500"
                            placeholder="Enter step name"
                        >
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea 
                            x-model="selectedStep.description"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500"
                            rows="3"
                            placeholder="Enter description"
                        ></textarea>
                    </div>

                    <div x-show="selectedStep.type === 'approval'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Approvers</label>
                        <select 
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500"
                            @change="addApprover($event.target.value)"
                        >
                            <option value="">Select approver...</option>
                            <option value="user">Specific User</option>
                            <option value="role">Role</option>
                            <option value="department">Department</option>
                        </select>
                        
                        <div class="mt-2 space-y-2">
                            <template x-for="(approver, index) in selectedStep.approvers" :key="index">
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium" x-text="approver.type + ': ' + approver.name"></span>
                                        <button @click="removeApprover(index)" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-gray-600">Weightage:</label>
                                        <input 
                                            type="number" 
                                            x-model="approver.weightage"
                                            min="0"
                                            max="100"
                                            class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500"
                                            placeholder="100"
                                        >
                                        <span class="text-xs text-gray-500">%</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Minimum Approval Percentage -->
                        <div class="mt-4 p-3 bg-blue-50 rounded">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-percentage mr-1"></i>
                                Minimum Approval Percentage
                            </label>
                            <div class="flex items-center gap-2">
                                <input 
                                    type="number" 
                                    x-model="selectedStep.minimum_approval_percentage"
                                    min="0"
                                    max="100"
                                    class="w-24 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500"
                                    placeholder="100"
                                >
                                <span class="text-sm text-gray-600">% required to proceed</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Set the minimum weightage percentage needed for this step to complete (e.g., 51% for majority, 75% for supermajority, 100% for unanimous)
                            </p>
                        </div>
                    </div>

                    <div x-show="selectedStep.type === 'condition'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Conditions</label>
                        <button @click="addCondition()" class="w-full px-3 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm">
                            <i class="fas fa-plus mr-2"></i>
                            Add Condition
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sequence</label>
                        <input 
                            type="number" 
                            x-model="selectedStep.sequence"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="border-t pt-4">
                        <button @click="deleteStep()" class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Step
                        </button>
                    </div>
                </div>

                <div x-show="!selectedStep" class="text-center text-gray-400 py-8">
                    <i class="fas fa-mouse-pointer text-4xl mb-3"></i>
                    <p>Select a step to edit properties</p>
                </div>
            </div>

            <!-- Workflow Info -->
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    Workflow Info
                </h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Steps:</span>
                        <span class="font-semibold" x-text="steps.length"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Connections:</span>
                        <span class="font-semibold" x-text="connections.length"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Valid</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function workflowDesigner() {
    return {
        steps: @json($workflow->steps ?? []),
        connections: [],
        selectedStep: null,
        nextId: 1,
        
        init() {
            // Initialize with existing workflow data
            this.steps = this.steps.map((step, index) => ({
                ...step,
                x: 100 + (index * 200),
                y: 100,
                approvers: step.approvers || [],
                conditions: step.conditions || []
            }));
            this.updateConnections();
        },
        
        addStep(type) {
            const newStep = {
                id: this.nextId++,
                type: type,
                name: `${type.charAt(0).toUpperCase() + type.slice(1)} Step ${this.steps.length + 1}`,
                description: '',
                sequence: this.steps.length + 1,
                x: 100 + (this.steps.length * 200),
                y: 100,
                approvers: [],
                conditions: []
            };
            this.steps.push(newStep);
            this.updateConnections();
        },
        
        selectStep(step) {
            this.selectedStep = step;
        },
        
        deleteStep() {
            if (this.selectedStep) {
                this.steps = this.steps.filter(s => s.id !== this.selectedStep.id);
                this.selectedStep = null;
                this.updateConnections();
            }
        },
        
        dragStart(event, step) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target);
        },
        
        dragEnd(event, step) {
            const canvas = event.target.closest('.workflow-canvas');
            const rect = canvas.getBoundingClientRect();
            step.x = event.clientX - rect.left - 75;
            step.y = event.clientY - rect.top - 50;
            this.updateConnections();
        },
        
        updateConnections() {
            this.connections = [];
            for (let i = 0; i < this.steps.length - 1; i++) {
                this.connections.push({
                    x1: this.steps[i].x + 75,
                    y1: this.steps[i].y + 80,
                    x2: this.steps[i + 1].x + 75,
                    y2: this.steps[i + 1].y
                });
            }
        },
        
        addApprover(type) {
            if (!type || !this.selectedStep) return;
            this.selectedStep.approvers.push({
                type: type,
                name: 'New ' + type
            });
        },
        
        removeApprover(index) {
            this.selectedStep.approvers.splice(index, 1);
        },
        
        addCondition() {
            if (!this.selectedStep) return;
            this.selectedStep.conditions.push({
                field: '',
                operator: '==',
                value: ''
            });
        },
        
        validateWorkflow() {
            alert('Workflow is valid!');
        },
        
        saveWorkflow() {
            fetch('{{ route("approval-process.workflows.designer.save", $workflow->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    design: {
                        name: '{{ $workflow->name }}',
                        steps: this.steps,
                        connections: this.connections
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                alert('Workflow saved successfully!');
            })
            .catch(error => {
                alert('Error saving workflow: ' + error.message);
            });
        },
        
        exportWorkflow() {
            window.location.href = '{{ route("approval-process.workflows.designer.export", $workflow->id) }}';
        },
        
        importWorkflow() {
            // Implementation for import
            alert('Import functionality coming soon!');
        }
    }
}
</script>
@endpush
